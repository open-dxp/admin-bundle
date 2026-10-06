# Testing

The admin bundle is tested with Pest and the [OpenDXP test foundation](https://github.com/open-dxp/test-foundation).
The foundation builds an OpenDXP application around the bundle and installs it. The tests run in
that application.

## Running the tests

From your checkout of `open-dxp/admin-bundle`, with the [OpenDXP testkit](https://github.com/open-dxp/docker-testkit):

```bash
testkit test
testkit test tests/Feature/Tree
testkit test --filter="lists the children a user may see"
testkit analyse
```

The first run builds the application and takes a few minutes. After that, a single file takes a
few seconds.

## Where things live

```
tests/
    Feature/        tests that boot the application, one directory per subject
    Unit/           tests that do not boot it
    Application/    the kernel of the test application
    TestCase/       the test cases this suite adds
    Factory/        factories for the test classes of this suite
    Helpers/        helper functions
    Fixtures/       class definitions the suite installs
```

Factories for OpenDXP's own models come from the core, in `OpenDxp\Test\Factory`.

## Test cases

| Test case           | Directory                             | Why it differs                                                    |
|---------------------|---------------------------------------|-------------------------------------------------------------------|
| `TestCase`          | `Feature`, except `Feature/LoginLink` | Every test runs in a transaction that is rolled back.             |
| `LoginLinkTestCase` | `Feature/LoginLink`                   | It resolves the host of a login link against sites and providers. |

## What to test where

The logic of the bundle lives in `src/Handler/` and `src/Service/`, not in the controllers. Test a
handler or a service directly. Test through a request when the request itself matters, for example
the permission check of a controller or what the tree lists for a user.

A payload reads a request in `fromRequest()`. Test that method in `tests/Unit` with a `Request` of
your own.

## Contributing tests

Tests are very welcome. Name a test with a sentence that says what is guaranteed, put it into the
directory of its subject, and create its data with a factory.
