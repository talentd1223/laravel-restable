# Laravel Restable

## Installation

You can install the package via composer:

```bash
composer require talentd1223/laravel-restable
```

## Prerequisite

To associate search with a model, the model must implement the following interface and trait:

```php

namespace App\Models;

use talentd1223\LaravelRestable\HasRestable;
use talentd1223\LaravelRestable\Restable;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model implements Restable
{
    use HasRestable;
}
```

## Usage

Using this lightweight package you can mainly customize `search`, `matches` and `sorts`.

### Search

Define columns you want to be searchable using `$search` model property:

```php
class Dream extends Model implements Restable
{
    use HasRestable;
    
    public static array $search = [
        'name',
    ];
}
```

Now in the query of your request you can use the `?search=` to find over your model. Let's assume this is the URL for
geting the list of `dreams`:

```http request
GET: /api/dreams?search=be happy
```

Then in the controller you may have something like this:

```php
use App\Models\Dream;
use Illuminate\Http\Request;

class DreamController extends Controller
{
    public function index(Request $request)
    {
        $dreams = Dream::search($request);
        
        return response()->json($dreams->paginate());
    }
}
```

This way Restable will find your `dreams` by `name` column and will return a `Builder` instance, so you can `paginate`
or do whatever you want over that query.

The query filtering is something like this: `$query->where('column_name', 'like', "%$value%"`;

## Match

Matching by a specific column is a more strict type of search. You should define the columns you want to match along
with the type:

```php
use talentd1223\LaravelRestable\Types;

class Dream extends Model implements Restable
{
    use HasRestable;
    
    public static array $match = [
        'id' => Types::MATCH_ARRAY,
        'name' => Types::MATCH_TEXT,
    ];
}
```

The URL may look like this:

```http request
GET: /api/dreams?id=1,2,3&name=happy
```

So Restable will make a query like this:

```php
$query->whereIn('id', [1, 2, 3])->where('name','=', 'happy');
```

The controller could be same as we had for the search.

### Sort

You can also specify what columns could be sortable:

```php
class Dream extends Model implements Restable
{
    use HasRestable;
    
    public static array $sort = [
        'id',
        'name',
    ];
}
```

The query params for sort could indicate whatever is `asc` or `desc` sorting by using the `-` sign:

Sorting `desc` by `id` column:

```http request
GET: /api/dreams?sort=-id
```

Sorting `asc` by `id` column:

```http request
GET: /api/dreams?sort=id
```

## Customizations

### Model Methods

You can use methods to return your `search`, `matches` or `sorts` from the model definition:

```php
class Dream extends Model implements Restable
{
    use HasRestable;
    
    public static function sorts(): array
    {
        return [ 'id', 'name' ];
    }

    public static function matches(): array
    {
        return [
            'id' => 'int',
            'name' => 'string',
        ];
    }

    public static function searchables(): array
    {
        return ['name'];
    }
}
```

### Custom filters

Instead of using the default methods for filtering, you can have your own:

```php
use talentd1223\LaravelRestable\Filters\MatchFilter;class Dream extends Model implements Restable
{
    use HasRestable;

    public static function matches(): array
    {
        return [
            'something' => MatchFilter::make()->resolveUsing(function($request, $query) {
                   // filter it here
            }),
            'name' => 'string'
        ];
    }
}
```

So you can now match by `something` property, and implement your own search into the closure. 

You can also create your own `Match` filter class, and implement the search there:

```php
use talentd1223\LaravelRestable\Filters\MatchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MatchSomething extends MatchFilter
{
        public function apply(Request $request, Builder $query, $value): Builder
        {
            // your filters
            return $query->where('a', $value);
        }

}
```

Then use your `MatchSomething` filter:


```php
class Dream extends Model implements Restable
{
    use HasRestable;

    public static function matches(): array
    {
        return [
            'something' => MatchSomething::make(),
            'name' => 'string'
        ];
    }
}
```

The same you could do for `Search` or `Sort` filter, by extending the `talentd1223\LaravelRestable\Filters\SearchableFilter` or `talentd1223\LaravelRestable\Filters\SortableFilter` filters.

## Testing

```bash
composer test
```

