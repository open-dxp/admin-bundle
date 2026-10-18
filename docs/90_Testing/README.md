# Testing

The admin bundle ships classes to test a grid export source, in the namespace
`OpenDxp\Bundle\AdminBundle\Test\GridExport`. This page builds on
[Testing](https://docs.opendxp.io/docs/core-framework/Development_Tools_and_Details/Testing).

## Test API

| Class            | What it gives                                                                                                                             |
|------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| `GridExports`    | `export()` runs an export of a source for a user and returns its file.                                                                    |
| `GridExportFile` | `getRows()`, `getHeader()` and `getColumn()` read the rows of a CSV or XLSX file. `getWorksheet()` returns the sheet XML of an XLSX file. |

[Test a source](../10_Extension_Points/08_Grid_Export.md#test-a-source) shows a test.

## The suite of this bundle

| Test case                        | Directory                  | Why it differs                                                                              |
|----------------------------------|----------------------------|---------------------------------------------------------------------------------------------|
| `GenericExecutionEngineTestCase` | `Feature/GridExportJobRun` | The tests handle job messages like a worker, in the environment `generic_execution_engine`. |
| `LoginLinkTestCase`              | `Feature/LoginLink`        | It resolves the host of a login link against sites and providers.                           |

The logic of the bundle lives in `src/Handler/` and `src/Service/`, not in the controllers. Test a
handler or a service directly. Test through a request when the request itself matters, for example
the permission check of a controller or what the tree lists for a user.

A payload reads a request in `fromRequest()`. Test that method in `tests/Unit` with a `Request` of
your own.
