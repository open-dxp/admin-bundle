# Tests — Claude Instructions

## Framework & Setup

Tests use **Codeception**. Configuration: `codeception.dist.yml` in the bundle root.

The bootstrap (`tests/_bootstrap.php`) resolves the OpenDXP environment in this order:
1. `vendor/autoload.php` in the bundle itself (standalone)
2. `../../../../vendor/autoload.php` (installed as part of a project)
3. `$OPENDXP_PROJECT_ROOT/vendor/autoload.php` (via env variable)

Integration tests require a fully running OpenDXP environment with a database.

## Directory Structure

```
tests/
├── Model/                        # Integration tests (need DB + running OpenDXP)
│   ├── GridHelper/
│   │   └── GridHelperTest.php
│   └── Permissions/
│       ├── AbstractPermissionTest.php
│       ├── ModelAssetPermissionsTest.php
│       ├── ModelDataObjectPermissionsTest.php
│       └── ModelDocumentPermissionsTest.php
├── Unit/                         # Unit tests (no DB, no OpenDXP bootstrap)
│   └── Event/                    # ← place event/enum unit tests here
└── Support/
    ├── Helper/
    │   └── Model.php
    └── UnitTester.php
```

`tests/Unit/` does not exist yet — create it when adding the first unit test.

## Base Classes

| Situation | Base Class | Location |
|---|---|---|
| Test needs DB, models, real OpenDXP objects | `ModelTestCase` | `OpenDxp\Tests\Support\Test\ModelTestCase` (from `../opendxp`) |
| Pure logic, no I/O, no DB | `TestCase` | `PHPUnit\Framework\TestCase` |

**Rule of thumb:**
- New event class or enum → `PHPUnit\Framework\TestCase`
- Controller behaviour, permissions, grid queries → `ModelTestCase`

Never use `ModelTestCase` for things that don't need the DB — it requires a full OpenDXP bootstrap and slows everything down.

## Unit Test — Event Classes

Event classes are pure data containers and easy to test without any infrastructure.

Example: `ElementAdminStyleEvent` carries the element, its admin style, and an optional
context (tree, editor, search). A unit test verifies the getters, setters, and context constants:

```php
<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Event;

use OpenDxp\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\UnitTestCase;
use OpenDxp\Model\Element\AdminStyle;
use OpenDxp\Model\Element\ElementInterface;

class ElementAdminStyleEventTest extends UnitTestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $element    = $this->createMock(ElementInterface::class);
        $adminStyle = new AdminStyle($element);

        $event = new ElementAdminStyleEvent($element, $adminStyle, ElementAdminStyleEvent::CONTEXT_TREE);

        self::assertSame($element, $event->getElement());
        self::assertSame($adminStyle, $event->getAdminStyle());
        self::assertSame(ElementAdminStyleEvent::CONTEXT_TREE, $event->getContext());
    }

    public function testContextIsNullByDefault(): void
    {
        $element = $this->createMock(ElementInterface::class);

        $event = new ElementAdminStyleEvent($element, new AdminStyle($element));

        self::assertNull($event->getContext());
    }

    public function testSetContextUpdatesValue(): void
    {
        $element = $this->createMock(ElementInterface::class);
        $event   = new ElementAdminStyleEvent($element, new AdminStyle($element));

        $event->setContext(ElementAdminStyleEvent::CONTEXT_EDITOR);

        self::assertSame(ElementAdminStyleEvent::CONTEXT_EDITOR, $event->getContext());
    }

    public function testContextConstantsAreDistinct(): void
    {
        self::assertNotSame(ElementAdminStyleEvent::CONTEXT_TREE,   ElementAdminStyleEvent::CONTEXT_EDITOR);
        self::assertNotSame(ElementAdminStyleEvent::CONTEXT_EDITOR, ElementAdminStyleEvent::CONTEXT_SEARCH);
        self::assertNotSame(ElementAdminStyleEvent::CONTEXT_TREE,   ElementAdminStyleEvent::CONTEXT_SEARCH);
    }
}
```

Note: `ElementInterface` is an interface and can be mocked normally. For final model classes
(like `Site`), use `new ClassName()` directly instead of `createMock()` — see `UnitTestCase::createSite()`
as an example of how to encapsulate that in the base class.

## Integration Test — Permissions / Model

Integration tests extend `AbstractPermissionTest` which itself extends `ModelTestCase`.
They use `Codeception\Stub` to wire controllers without a full HTTP stack.

See `tests/Model/Permissions/ModelDocumentPermissionsTest.php` for a working example.

Key pattern:
```php
// build a stubbed controller with a mocked user
$controller = $this->buildController(DocumentController::class, $user);

// call the action directly
$response = $controller->treeGetChildrenByIdAction($request);

// assert on the JSON response
$data = json_decode($response->getContent(), true);
self::assertTrue($data['success']);
```

## Naming Conventions

- Unit tests: `tests/Unit/{Namespace}/{ClassName}Test.php`
- Integration tests: `tests/Model/{Feature}/{ClassName}Test.php`
- Test methods: `test` prefix + descriptive camelCase (`testAddConfigNodeGroupsByScope`)
- One `Test.php` per source class being tested

*** 

## Running Tests

### Prerequisites
First time starts: Is the MCP tool `opendxp-testkit` available in this session?

**No → Run setup now:**

Ask the developer this question and wait for the answer:
> "Where is your local `docker-testkit` directory? (absolute path)"

Once the path is provided:

- Write `.mcp.json` in the bundle root:

```json
{
    "mcpServers": {
        "opendxp-testkit": {
            "type": "stdio",
            "command": "node",
            "args": [
                "<PATH>/mcp-server/index.js"
            ]
        }
    }
}
```

- Add `.mcp.json` to `.gitignore` if not already present
- Tell the developer: "Please restart Claude Code — `opendxp-testkit` will be available after restart."
- Stop. Wait for restart.

**Yes → Normal workflow:**

---

### Workflow: Running tests

#### Step 1 — Check status

Call `get_status()`. The result shows:

- `Configured bundle` — which bundle is currently set in the testkit
- `ddev running` — whether ddev is running

Decide based on the result:

| Situation                            | Action                                                                                   |
|--------------------------------------|------------------------------------------------------------------------------------------|
| `ddev running: false`                | Call `set_bundle("BUNDLE_DIR_NAME")` → ddev will be started → use `with_composer=true`   |
| `ddev running: true`, wrong bundle   | Call `set_bundle("BUNDLE_DIR_NAME")` → ddev will be restarted → use `with_composer=true` |
| `ddev running: true`, correct bundle | Proceed to step 2 — no `with_composer` needed                                            |

`BUNDLE_DIR_NAME` = `basename` of this directory (e.g. `ecommerce-bundle`)

> **Note on rsync:** Files are **always** synced into the container via rsync — no ddev restart is needed after code changes.

#### Step 2 — Run tests

```
run_codeception(test_path="tests/...", with_composer=true/false)
```

- Only set `test_path` when the user specifies a particular test, otherwise omit it (runs all tests)
- Do **not** set `debug` by default (see rules below)

**`test_path` formats:**
| What the user wants | `test_path` value |
|---------------------|-------------------|
| All tests | *(omit)* |
| A specific test class | `tests/Model//GridHelper/GridHelperTest.php` |
| A specific test folder | `tests/Unit/Event` |
| All unit tests | `tests/Unit` |
| All model/integration tests | `tests/Model` |

#### Step 3 — On test failure

If tests fail, ask the user:
> "Tests failed. Should I re-run with `--debug` for detailed output?"

Only if the user agrees: `run_codeception(debug=true, ...)`

---

### Workflow: PHPStan

`run_phpstan()` is a standalone task — only run it when the user explicitly asks for it.

```
run_phpstan(level=6)    ← default level, adjust on request
```

---

### Rules

- Write code and tests in this directory only — never touch `app/` inside the testkit
- `with_composer=true` after `set_bundle` calls or if something changed in `composer.json`
- `debug=true` only with explicit user consent after a failed test run
- PHPStan only on explicit request

---

### Fallback: Without MCP

Only use these commands if `opendxp-testkit` is not available in this session:

```bash
# all suites
vendor/bin/codecept run

# unit tests only (no DB needed)
vendor/bin/codecept run Unit

# integration tests only (DB required)
vendor/bin/codecept run Model

# single file
vendor/bin/codecept run Unit tests/Unit/Event/SiteCustomSettingsEventTest.php
```

For integration tests, set `OPENDXP_PROJECT_ROOT` if running outside a full project:
```bash
OPENDXP_PROJECT_ROOT=/path/to/project vendor/bin/codecept run Model
```