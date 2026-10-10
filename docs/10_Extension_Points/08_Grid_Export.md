# Grid Export

Every grid of the admin exports its rows through one export. The editor picks CSV or XLSX, the header and, in a grid
with a checkbox column, whether only the selected rows go into the file. The export holds the rows that the grid
shows: its filters, its search and its sorting.

The admin writes an export in batches, one request per batch, and shows the progress in a window. Above a number of
rows, it asks before it starts.

## Formats

- CSV starts with a UTF-8 byte order mark, so Excel reads umlauts. The editor picks the delimiter.
- XLSX keeps the type of each value: numbers, dates and booleans stay numbers, dates and booleans. The header row stays
  visible while scrolling and filters its column.
- A value that starts like a formula, for example with `=`, stays text in both formats.

## Configure the confirmation

The admin asks before it exports more rows than this:

```yaml
opendxp_admin:
    grid_export:
        confirm_threshold: 1000
```

## Add a source

A source provides the rows of one grid. It implements `GridExportSourceInterface`, and the attribute
`#[AsGridExportSource]` registers it:

```php
<?php

namespace App\GridExport;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Model\DataObject\Product;

#[AsGridExportSource(name: 'products', permission: 'opendxp:security:permission:products')]
final class ProductGridExportSource implements GridExportSourceInterface
{
    public function getColumns(GridExportQuery $query): array
    {
        return [
            new GridExportColumn('sku', 'SKU'),
            new GridExportColumn('price', 'Price', GridExportColumnType::FLOAT),
        ];
    }

    public function countRows(GridExportQuery $query): int
    {
        return $this->createListing($query)->getTotalCount();
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $listing = $this->createListing($query);
        $listing->setOffset($offset);
        $listing->setLimit($limit);

        foreach ($listing as $product) {
            yield [
                'sku' => $product->getSku(),
                'price' => $product->getPrice(),
            ];
        }
    }

    private function createListing(GridExportQuery $query): Product\Listing
    {
        $listing = new Product\Listing();
        if ($query->selectedIds !== []) {
            $listing->setCondition('oo_id IN (?)', [$query->selectedIds]);
        }

        return $listing;
    }
}
```

- The name identifies the source in the requests of the admin.
- A user needs the permission to start an export. It is an attribute of the Symfony security, like the ones of
  `CorePermission`.
- `batchSize` limits the rows of one request. It defaults to 500.
- `GridExportQuery` holds the parameters of the grid, the selected IDs, the user, the language and the timezone. A
  source reads the rows the same way the grid lists them.
- Every row holds the value of each column under the key of the column. The export stops when a row misses a column
  or holds one that the source does not declare.
- A value matches the type of its column. A date is a `DateTimeInterface`, and `null` stands for an empty value.

## Start an export in the admin

`opendxp.element.gridexport.runner` asks for the settings, writes the batches and downloads the file:

```javascript
new opendxp.element.gridexport.runner({
    source: 'products',
    getParameters: function () {
        return opendxp.element.gridexport.runner.getStoreParameters(this.store);
    }.bind(this),
    filters: {filter: ''},
    getSelectedIds: function () {
        return this.grid.getSelectionModel().getSelection().map(function (record) {
            return record.get('id');
        });
    }.bind(this)
}).start();
```

- `getStoreParameters()` returns what the store of a grid sends to load its rows: the extra parameters, the filters
  and the sorting.
- `filters` maps each parameter that filters the grid to its value without a filter. When a parameter holds another
  value, the admin asks whether the export holds only the filtered rows. With "All rows", the export sets these
  parameters back to their value without a filter.
- `getSelectedIds` belongs only to a grid with a checkbox column. When the editor selected rows, the admin asks whether
  the export holds only them.
- `warnings` shows a note per format on top of the dialog, for example `{csv: t('my_csv_note')}`.
- `settings` adds fields of the grid to the dialog. Their values go into the parameters.

## Test a source

`GridExports::export()` runs an export and returns its file. `GridExportFile` reads its rows:

```php
use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExports;

it('exports the price of a product', function () {
    ProductFactory::createOne(['sku' => 'A-1', 'price' => 9.5]);

    $file = GridExports::export($this->admin, 'products');

    expect($file)
        ->getColumn('Price')
        ->toBe(['9.5']);
});
```
