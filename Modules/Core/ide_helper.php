<?php
namespace Illuminate\Http {

    use Illuminate\Contracts\Support\Arrayable;
    use Illuminate\Contracts\Support\Jsonable;
    use Illuminate\Contracts\Support\Renderable;
    use Illuminate\Support\Traits\Macroable;
    use Modules\Core\Providers\CoreServiceProvider;
    use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
    use Symfony\Component\HttpFoundation\ResponseHeaderBag;

    class Response extends SymfonyResponse
    {
        use ResponseTrait, Macroable {
            Macroable::__call as macroCall;
        }

        /**
         * Create a new JSON response instance.
         *
         * @param  mixed  $data
         * @param  int  $status
         * @param  array  $headers
         * @param  int  $options
         * @return \Illuminate\Http\JsonResponse
         * @see CoreServiceProvider loadMacros
         */
        public function success($message, $data = null, $httpCode = 200)
        {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]);
        }

        /**
         * Create a new JSON response instance.
         *
         * @param  mixed  $data
         * @param  int  $status
         * @param  array  $headers
         * @param  int  $options
         * @return \Illuminate\Http\JsonResponse
         * @see CoreServiceProvider loadMacros
         */
        public function error($message, $data = null, $httpCode = 400)
        {
            if ($httpCode == 422) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $data,
                ], $httpCode);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $data,
            ], $httpCode);
        }

        /**
         * Create a new HTTP response.
         *
         * @param  mixed  $content
         * @param  int  $status
         * @param  array  $headers
         * @return void
         *
         * @throws \InvalidArgumentException
         */
        public function __construct($content = '', $status = 200, array $headers = [])
        {
            $this->headers = new ResponseHeaderBag($headers);

            $this->setContent($content);
            $this->setStatusCode($status);
            $this->setProtocolVersion('1.0');
        }

        /**
         * Set the content on the response.
         *
         * @param  mixed  $content
         * @return $this
         *
         * @throws \InvalidArgumentException
         */
        public function setContent($content)
        {
            $this->original = $content;

            // If the content is "JSONable" we will set the appropriate header and convert
            // the content to JSON. This is useful when returning something like models
            // from routes that will be automatically transformed to their JSON form.
            if ($this->shouldBeJson($content)) {
                $this->header('Content-Type', 'application/json');

                $content = $this->morphToJson($content);

                if ($content === false) {
                    throw new InvalidArgumentException(json_last_error_msg());
                }
            }

            // If this content implements the "Renderable" interface then we will call the
            // render method on the object so we will avoid any "__toString" exceptions
            // that might be thrown and have their errors obscured by PHP's handling.
            elseif ($content instanceof Renderable) {
                $content = $content->render();
            }

            parent::setContent($content);

            return $this;
        }

        /**
         * Determine if the given content should be turned into JSON.
         *
         * @param  mixed  $content
         * @return bool
         */
        protected function shouldBeJson($content)
        {
            return $content instanceof Arrayable ||
                $content instanceof Jsonable ||
                $content instanceof ArrayObject ||
                $content instanceof JsonSerializable ||
                is_array($content);
        }

        /**
         * Morph the given content into JSON.
         *
         * @param  mixed  $content
         * @return string
         */
        protected function morphToJson($content)
        {
            if ($content instanceof Jsonable) {
                return $content->toJson();
            } elseif ($content instanceof Arrayable) {
                return json_encode($content->toArray());
            }

            return json_encode($content);
        }
    }
}

namespace Illuminate\Database\Schema {

    use Illuminate\Database\Connection;
    use Illuminate\Database\Query\Expression;
    use Illuminate\Database\Schema\Grammars\Grammar;
    use Illuminate\Database\SQLiteConnection;
    use Illuminate\Support\Fluent;
    use Illuminate\Support\Traits\Macroable;

    class Blueprint
    {
        use Macroable;


        public function authors()
        {
            $this->foreignId('creator_id');

            return $this->foreignId('user_id');
        }

        public function morphAuthors()
        {
            $this->morphs('creator');

            return $this->morphs('user');
        }


        /**
         * The table the blueprint describes.
         *
         * @var string
         */
        protected $table;

        /**
         * The prefix of the table.
         *
         * @var string
         */
        protected $prefix;

        /**
         * The columns that should be added to the table.
         *
         * @var \Illuminate\Database\Schema\ColumnDefinition[]
         */
        protected $columns = [];

        /**
         * The commands that should be run for the table.
         *
         * @var \Illuminate\Support\Fluent[]
         */
        protected $commands = [];

        /**
         * The storage engine that should be used for the table.
         *
         * @var string
         */
        public $engine;

        /**
         * The default character set that should be used for the table.
         *
         * @var string
         */
        public $charset;

        /**
         * The collation that should be used for the table.
         *
         * @var string
         */
        public $collation;

        /**
         * Whether to make the table temporary.
         *
         * @var bool
         */
        public $temporary = false;

        /**
         * The column to add new columns after.
         *
         * @var string
         */
        public $after;

        /**
         * Create a new schema blueprint.
         *
         * @param string $table
         * @param \Closure|null $callback
         * @param string $prefix
         * @return void
         */
        public function __construct($table, Closure $callback = null, $prefix = '')
        {
            $this->table = $table;
            $this->prefix = $prefix;

            if (!is_null($callback)) {
                $callback($this);
            }
        }

        /**
         * Execute the blueprint against the database.
         *
         * @param \Illuminate\Database\Connection $connection
         * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
         * @return void
         */
        public function build(Connection $connection, Grammar $grammar)
        {
            foreach ($this->toSql($connection, $grammar) as $statement) {
                $connection->statement($statement);
            }
        }

        /**
         * Get the raw SQL statements for the blueprint.
         *
         * @param \Illuminate\Database\Connection $connection
         * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
         * @return array
         */
        public function toSql(Connection $connection, Grammar $grammar)
        {
            $this->addImpliedCommands($grammar);

            $statements = [];

            // Each type of command has a corresponding compiler function on the schema
            // grammar which is used to build the necessary SQL statements to build
            // the blueprint element, so we'll just call that compilers function.
            $this->ensureCommandsAreValid($connection);

            foreach ($this->commands as $command) {
                $method = 'compile' . ucfirst($command->name);

                if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                    if (!is_null($sql = $grammar->$method($this, $command, $connection))) {
                        $statements = array_merge($statements, (array)$sql);
                    }
                }
            }

            return $statements;
        }

        /**
         * Ensure the commands on the blueprint are valid for the connection type.
         *
         * @param \Illuminate\Database\Connection $connection
         * @return void
         *
         * @throws \BadMethodCallException
         */
        protected function ensureCommandsAreValid(Connection $connection)
        {
            if ($connection instanceof SQLiteConnection) {
                if ($this->commandsNamed(['dropColumn', 'renameColumn'])->count() > 1) {
                    throw new BadMethodCallException(
                        "SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification."
                    );
                }

                if ($this->commandsNamed(['dropForeign'])->count() > 0) {
                    throw new BadMethodCallException(
                        "SQLite doesn't support dropping foreign keys (you would need to re-create the table)."
                    );
                }
            }
        }

        /**
         * Get all of the commands matching the given names.
         *
         * @param array $names
         * @return \Illuminate\Support\Collection
         */
        protected function commandsNamed(array $names)
        {
            return collect($this->commands)->filter(function ($command) use ($names) {
                return in_array($command->name, $names);
            });
        }

        /**
         * Add the commands that are implied by the blueprint's state.
         *
         * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
         * @return void
         */
        protected function addImpliedCommands(Grammar $grammar)
        {
            if (count($this->getAddedColumns()) > 0 && !$this->creating()) {
                array_unshift($this->commands, $this->createCommand('add'));
            }

            if (count($this->getChangedColumns()) > 0 && !$this->creating()) {
                array_unshift($this->commands, $this->createCommand('change'));
            }

            $this->addFluentIndexes();

            $this->addFluentCommands($grammar);
        }

        /**
         * Add the index commands fluently specified on columns.
         *
         * @return void
         */
        protected function addFluentIndexes()
        {
            foreach ($this->columns as $column) {
                foreach (['primary', 'unique', 'index', 'spatialIndex'] as $index) {
                    // If the index has been specified on the given column, but is simply equal
                    // to "true" (boolean), no name has been specified for this index so the
                    // index method can be called without a name and it will generate one.
                    if ($column->{$index} === true) {
                        $this->{$index}($column->name);
                        $column->{$index} = false;

                        continue 2;
                    }

                    // If the index has been specified on the given column, and it has a string
                    // value, we'll go ahead and call the index method and pass the name for
                    // the index since the developer specified the explicit name for this.
                    elseif (isset($column->{$index})) {
                        $this->{$index}($column->name, $column->{$index});
                        $column->{$index} = false;

                        continue 2;
                    }
                }
            }
        }

        /**
         * Add the fluent commands specified on any columns.
         *
         * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
         * @return void
         */
        public function addFluentCommands(Grammar $grammar)
        {
            foreach ($this->columns as $column) {
                foreach ($grammar->getFluentCommands() as $commandName) {
                    $attributeName = lcfirst($commandName);

                    if (!isset($column->{$attributeName})) {
                        continue;
                    }

                    $value = $column->{$attributeName};

                    $this->addCommand(
                        $commandName, compact('value', 'column')
                    );
                }
            }
        }

        /**
         * Determine if the blueprint has a create command.
         *
         * @return bool
         */
        public function creating()
        {
            return collect($this->commands)->contains(function ($command) {
                return $command->name === 'create';
            });
        }

        /**
         * Indicate that the table needs to be created.
         *
         * @return \Illuminate\Support\Fluent
         */
        public function create()
        {
            return $this->addCommand('create');
        }

        /**
         * Indicate that the table needs to be temporary.
         *
         * @return void
         */
        public function temporary()
        {
            $this->temporary = true;
        }

        /**
         * Indicate that the table should be dropped.
         *
         * @return \Illuminate\Support\Fluent
         */
        public function drop()
        {
            return $this->addCommand('drop');
        }

        /**
         * Indicate that the table should be dropped if it exists.
         *
         * @return \Illuminate\Support\Fluent
         */
        public function dropIfExists()
        {
            return $this->addCommand('dropIfExists');
        }

        /**
         * Indicate that the given columns should be dropped.
         *
         * @param array|mixed $columns
         * @return \Illuminate\Support\Fluent
         */
        public function dropColumn($columns)
        {
            $columns = is_array($columns) ? $columns : func_get_args();

            return $this->addCommand('dropColumn', compact('columns'));
        }

        /**
         * Indicate that the given columns should be renamed.
         *
         * @param string $from
         * @param string $to
         * @return \Illuminate\Support\Fluent
         */
        public function renameColumn($from, $to)
        {
            return $this->addCommand('renameColumn', compact('from', 'to'));
        }

        /**
         * Indicate that the given primary key should be dropped.
         *
         * @param string|array|null $index
         * @return \Illuminate\Support\Fluent
         */
        public function dropPrimary($index = null)
        {
            return $this->dropIndexCommand('dropPrimary', 'primary', $index);
        }

        /**
         * Indicate that the given unique key should be dropped.
         *
         * @param string|array $index
         * @return \Illuminate\Support\Fluent
         */
        public function dropUnique($index)
        {
            return $this->dropIndexCommand('dropUnique', 'unique', $index);
        }

        /**
         * Indicate that the given index should be dropped.
         *
         * @param string|array $index
         * @return \Illuminate\Support\Fluent
         */
        public function dropIndex($index)
        {
            return $this->dropIndexCommand('dropIndex', 'index', $index);
        }

        /**
         * Indicate that the given spatial index should be dropped.
         *
         * @param string|array $index
         * @return \Illuminate\Support\Fluent
         */
        public function dropSpatialIndex($index)
        {
            return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
        }

        /**
         * Indicate that the given foreign key should be dropped.
         *
         * @param string|array $index
         * @return \Illuminate\Support\Fluent
         */
        public function dropForeign($index)
        {
            return $this->dropIndexCommand('dropForeign', 'foreign', $index);
        }

        /**
         * Indicate that the given column and foreign key should be dropped.
         *
         * @param string $column
         * @return \Illuminate\Support\Fluent
         */
        public function dropConstrainedForeignId($column)
        {
            $this->dropForeign([$column]);

            return $this->dropColumn($column);
        }

        /**
         * Indicate that the given indexes should be renamed.
         *
         * @param string $from
         * @param string $to
         * @return \Illuminate\Support\Fluent
         */
        public function renameIndex($from, $to)
        {
            return $this->addCommand('renameIndex', compact('from', 'to'));
        }

        /**
         * Indicate that the timestamp columns should be dropped.
         *
         * @return void
         */
        public function dropTimestamps()
        {
            $this->dropColumn('created_at', 'updated_at');
        }

        /**
         * Indicate that the timestamp columns should be dropped.
         *
         * @return void
         */
        public function dropTimestampsTz()
        {
            $this->dropTimestamps();
        }

        /**
         * Indicate that the soft delete column should be dropped.
         *
         * @param string $column
         * @return void
         */
        public function dropSoftDeletes($column = 'deleted_at')
        {
            $this->dropColumn($column);
        }

        /**
         * Indicate that the soft delete column should be dropped.
         *
         * @param string $column
         * @return void
         */
        public function dropSoftDeletesTz($column = 'deleted_at')
        {
            $this->dropSoftDeletes($column);
        }

        /**
         * Indicate that the remember token column should be dropped.
         *
         * @return void
         */
        public function dropRememberToken()
        {
            $this->dropColumn('remember_token');
        }

        /**
         * Indicate that the polymorphic columns should be dropped.
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function dropMorphs($name, $indexName = null)
        {
            $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));

            $this->dropColumn("{$name}_type", "{$name}_id");
        }

        /**
         * Rename the table to a given name.
         *
         * @param string $to
         * @return \Illuminate\Support\Fluent
         */
        public function rename($to)
        {
            return $this->addCommand('rename', compact('to'));
        }

        /**
         * Specify the primary key(s) for the table.
         *
         * @param string|array $columns
         * @param string|null $name
         * @param string|null $algorithm
         * @return \Illuminate\Support\Fluent
         */
        public function primary($columns, $name = null, $algorithm = null)
        {
            return $this->indexCommand('primary', $columns, $name, $algorithm);
        }

        /**
         * Specify a unique index for the table.
         *
         * @param string|array $columns
         * @param string|null $name
         * @param string|null $algorithm
         * @return \Illuminate\Support\Fluent
         */
        public function unique($columns, $name = null, $algorithm = null)
        {
            return $this->indexCommand('unique', $columns, $name, $algorithm);
        }

        /**
         * Specify an index for the table.
         *
         * @param string|array $columns
         * @param string|null $name
         * @param string|null $algorithm
         * @return \Illuminate\Support\Fluent
         */
        public function index($columns, $name = null, $algorithm = null)
        {
            return $this->indexCommand('index', $columns, $name, $algorithm);
        }

        /**
         * Specify a spatial index for the table.
         *
         * @param string|array $columns
         * @param string|null $name
         * @return \Illuminate\Support\Fluent
         */
        public function spatialIndex($columns, $name = null)
        {
            return $this->indexCommand('spatialIndex', $columns, $name);
        }

        /**
         * Specify a raw index for the table.
         *
         * @param string $expression
         * @param string $name
         * @return \Illuminate\Support\Fluent
         */
        public function rawIndex($expression, $name)
        {
            return $this->index([new Expression($expression)], $name);
        }

        /**
         * Specify a foreign key for the table.
         *
         * @param string|array $columns
         * @param string|null $name
         * @return \Illuminate\Database\Schema\ForeignKeyDefinition
         */
        public function foreign($columns, $name = null)
        {
            $command = new ForeignKeyDefinition(
                $this->indexCommand('foreign', $columns, $name)->getAttributes()
            );

            $this->commands[count($this->commands) - 1] = $command;

            return $command;
        }

        /**
         * Create a new auto-incrementing big integer (8-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function id($column = 'id')
        {
            return $this->bigIncrements($column);
        }

        /**
         * Create a new auto-incrementing integer (4-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function increments($column)
        {
            return $this->unsignedInteger($column, true);
        }

        /**
         * Create a new auto-incrementing integer (4-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function integerIncrements($column)
        {
            return $this->unsignedInteger($column, true);
        }

        /**
         * Create a new auto-incrementing tiny integer (1-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function tinyIncrements($column)
        {
            return $this->unsignedTinyInteger($column, true);
        }

        /**
         * Create a new auto-incrementing small integer (2-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function smallIncrements($column)
        {
            return $this->unsignedSmallInteger($column, true);
        }

        /**
         * Create a new auto-incrementing medium integer (3-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function mediumIncrements($column)
        {
            return $this->unsignedMediumInteger($column, true);
        }

        /**
         * Create a new auto-incrementing big integer (8-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function bigIncrements($column)
        {
            return $this->unsignedBigInteger($column, true);
        }

        /**
         * Create a new char column on the table.
         *
         * @param string $column
         * @param int|null $length
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function char($column, $length = null)
        {
            $length = $length ?: Builder::$defaultStringLength;

            return $this->addColumn('char', $column, compact('length'));
        }

        /**
         * Create a new string column on the table.
         *
         * @param string $column
         * @param int|null $length
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function string($column, $length = null)
        {
            $length = $length ?: Builder::$defaultStringLength;

            return $this->addColumn('string', $column, compact('length'));
        }

        /**
         * Create a new text column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function text($column)
        {
            return $this->addColumn('text', $column);
        }

        /**
         * Create a new medium text column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function mediumText($column)
        {
            return $this->addColumn('mediumText', $column);
        }

        /**
         * Create a new long text column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function longText($column)
        {
            return $this->addColumn('longText', $column);
        }

        /**
         * Create a new integer (4-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function integer($column, $autoIncrement = false, $unsigned = false)
        {
            return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
        }

        /**
         * Create a new tiny integer (1-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
        {
            return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
        }

        /**
         * Create a new small integer (2-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function smallInteger($column, $autoIncrement = false, $unsigned = false)
        {
            return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
        }

        /**
         * Create a new medium integer (3-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
        {
            return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
        }

        /**
         * Create a new big integer (8-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function bigInteger($column, $autoIncrement = false, $unsigned = false)
        {
            return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
        }

        /**
         * Create a new unsigned integer (4-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedInteger($column, $autoIncrement = false)
        {
            return $this->integer($column, $autoIncrement, true);
        }

        /**
         * Create a new unsigned tiny integer (1-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedTinyInteger($column, $autoIncrement = false)
        {
            return $this->tinyInteger($column, $autoIncrement, true);
        }

        /**
         * Create a new unsigned small integer (2-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedSmallInteger($column, $autoIncrement = false)
        {
            return $this->smallInteger($column, $autoIncrement, true);
        }

        /**
         * Create a new unsigned medium integer (3-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedMediumInteger($column, $autoIncrement = false)
        {
            return $this->mediumInteger($column, $autoIncrement, true);
        }

        /**
         * Create a new unsigned big integer (8-byte) column on the table.
         *
         * @param string $column
         * @param bool $autoIncrement
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedBigInteger($column, $autoIncrement = false)
        {
            return $this->bigInteger($column, $autoIncrement, true);
        }

        /**
         * Create a new unsigned big integer (8-byte) column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
         */
        public function foreignId($column)
        {
            return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
                'type' => 'bigInteger',
                'name' => $column,
                'autoIncrement' => false,
                'unsigned' => true,
            ]));
        }

        /**
         * Create a foreign ID column for the given model.
         *
         * @param \Illuminate\Database\Eloquent\Model|string $model
         * @param string|null $column
         * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
         */
        public function foreignIdFor($model, $column = null)
        {
            if (is_string($model)) {
                $model = new $model;
            }

            return $model->getKeyType() === 'int' && $model->getIncrementing()
                ? $this->foreignId($column ?: $model->getForeignKey())
                : $this->foreignUuid($column ?: $model->getForeignKey());
        }

        /**
         * Create a new float column on the table.
         *
         * @param string $column
         * @param int $total
         * @param int $places
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function float($column, $total = 8, $places = 2, $unsigned = false)
        {
            return $this->addColumn('float', $column, compact('total', 'places', 'unsigned'));
        }

        /**
         * Create a new double column on the table.
         *
         * @param string $column
         * @param int|null $total
         * @param int|null $places
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function double($column, $total = null, $places = null, $unsigned = false)
        {
            return $this->addColumn('double', $column, compact('total', 'places', 'unsigned'));
        }

        /**
         * Create a new decimal column on the table.
         *
         * @param string $column
         * @param int $total
         * @param int $places
         * @param bool $unsigned
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function decimal($column, $total = 8, $places = 2, $unsigned = false)
        {
            return $this->addColumn('decimal', $column, compact('total', 'places', 'unsigned'));
        }

        /**
         * Create a new unsigned float column on the table.
         *
         * @param string $column
         * @param int $total
         * @param int $places
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedFloat($column, $total = 8, $places = 2)
        {
            return $this->float($column, $total, $places, true);
        }

        /**
         * Create a new unsigned double column on the table.
         *
         * @param string $column
         * @param int $total
         * @param int $places
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedDouble($column, $total = null, $places = null)
        {
            return $this->double($column, $total, $places, true);
        }

        /**
         * Create a new unsigned decimal column on the table.
         *
         * @param string $column
         * @param int $total
         * @param int $places
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function unsignedDecimal($column, $total = 8, $places = 2)
        {
            return $this->decimal($column, $total, $places, true);
        }

        /**
         * Create a new boolean column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function boolean($column)
        {
            return $this->addColumn('boolean', $column);
        }

        /**
         * Create a new enum column on the table.
         *
         * @param string $column
         * @param array $allowed
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function enum($column, array $allowed)
        {
            return $this->addColumn('enum', $column, compact('allowed'));
        }

        /**
         * Create a new set column on the table.
         *
         * @param string $column
         * @param array $allowed
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function set($column, array $allowed)
        {
            return $this->addColumn('set', $column, compact('allowed'));
        }

        /**
         * Create a new json column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function json($column)
        {
            return $this->addColumn('json', $column);
        }

        /**
         * Create a new jsonb column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function jsonb($column)
        {
            return $this->addColumn('jsonb', $column);
        }

        /**
         * Create a new date column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function date($column)
        {
            return $this->addColumn('date', $column);
        }

        /**
         * Create a new date-time column on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function dateTime($column, $precision = 0)
        {
            return $this->addColumn('dateTime', $column, compact('precision'));
        }

        /**
         * Create a new date-time column (with time zone) on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function dateTimeTz($column, $precision = 0)
        {
            return $this->addColumn('dateTimeTz', $column, compact('precision'));
        }

        /**
         * Create a new time column on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function time($column, $precision = 0)
        {
            return $this->addColumn('time', $column, compact('precision'));
        }

        /**
         * Create a new time column (with time zone) on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function timeTz($column, $precision = 0)
        {
            return $this->addColumn('timeTz', $column, compact('precision'));
        }

        /**
         * Create a new timestamp column on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function timestamp($column, $precision = 0)
        {
            return $this->addColumn('timestamp', $column, compact('precision'));
        }

        /**
         * Create a new timestamp (with time zone) column on the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function timestampTz($column, $precision = 0)
        {
            return $this->addColumn('timestampTz', $column, compact('precision'));
        }

        /**
         * Add nullable creation and update timestamps to the table.
         *
         * @param int $precision
         * @return void
         */
        public function timestamps($precision = 0)
        {
            $this->timestamp('created_at', $precision)->nullable();

            $this->timestamp('updated_at', $precision)->nullable();
        }

        /**
         * Add nullable creation and update timestamps to the table.
         *
         * Alias for self::timestamps().
         *
         * @param int $precision
         * @return void
         */
        public function nullableTimestamps($precision = 0)
        {
            $this->timestamps($precision);
        }

        /**
         * Add creation and update timestampTz columns to the table.
         *
         * @param int $precision
         * @return void
         */
        public function timestampsTz($precision = 0)
        {
            $this->timestampTz('created_at', $precision)->nullable();

            $this->timestampTz('updated_at', $precision)->nullable();
        }

        /**
         * Add a "deleted at" timestamp for the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function softDeletes($column = 'deleted_at', $precision = 0)
        {
            return $this->timestamp($column, $precision)->nullable();
        }

        /**
         * Add a "deleted at" timestampTz for the table.
         *
         * @param string $column
         * @param int $precision
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function softDeletesTz($column = 'deleted_at', $precision = 0)
        {
            return $this->timestampTz($column, $precision)->nullable();
        }

        /**
         * Create a new year column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function year($column)
        {
            return $this->addColumn('year', $column);
        }

        /**
         * Create a new binary column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function binary($column)
        {
            return $this->addColumn('binary', $column);
        }

        /**
         * Create a new uuid column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function uuid($column)
        {
            return $this->addColumn('uuid', $column);
        }

        /**
         * Create a new UUID column on the table with a foreign key constraint.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
         */
        public function foreignUuid($column)
        {
            return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
                'type' => 'uuid',
                'name' => $column,
            ]));
        }

        /**
         * Create a new IP address column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function ipAddress($column)
        {
            return $this->addColumn('ipAddress', $column);
        }

        /**
         * Create a new MAC address column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function macAddress($column)
        {
            return $this->addColumn('macAddress', $column);
        }

        /**
         * Create a new geometry column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function geometry($column)
        {
            return $this->addColumn('geometry', $column);
        }

        /**
         * Create a new point column on the table.
         *
         * @param string $column
         * @param int|null $srid
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function point($column, $srid = null)
        {
            return $this->addColumn('point', $column, compact('srid'));
        }

        /**
         * Create a new linestring column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function lineString($column)
        {
            return $this->addColumn('linestring', $column);
        }

        /**
         * Create a new polygon column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function polygon($column)
        {
            return $this->addColumn('polygon', $column);
        }

        /**
         * Create a new geometrycollection column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function geometryCollection($column)
        {
            return $this->addColumn('geometrycollection', $column);
        }

        /**
         * Create a new multipoint column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function multiPoint($column)
        {
            return $this->addColumn('multipoint', $column);
        }

        /**
         * Create a new multilinestring column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function multiLineString($column)
        {
            return $this->addColumn('multilinestring', $column);
        }

        /**
         * Create a new multipolygon column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function multiPolygon($column)
        {
            return $this->addColumn('multipolygon', $column);
        }

        /**
         * Create a new multipolygon column on the table.
         *
         * @param string $column
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function multiPolygonZ($column)
        {
            return $this->addColumn('multipolygonz', $column);
        }

        /**
         * Create a new generated, computed column on the table.
         *
         * @param string $column
         * @param string $expression
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function computed($column, $expression)
        {
            return $this->addColumn('computed', $column, compact('expression'));
        }

        /**
         * Add the proper columns for a polymorphic table.
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function morphs($name, $indexName = null)
        {
            if (Builder::$defaultMorphKeyType === 'uuid') {
                $this->uuidMorphs($name, $indexName);
            } else {
                $this->numericMorphs($name, $indexName);
            }
        }

        /**
         * Add nullable columns for a polymorphic table.
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function nullableMorphs($name, $indexName = null)
        {
            if (Builder::$defaultMorphKeyType === 'uuid') {
                $this->nullableUuidMorphs($name, $indexName);
            } else {
                $this->nullableNumericMorphs($name, $indexName);
            }
        }

        /**
         * Add the proper columns for a polymorphic table using numeric IDs (incremental).
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function numericMorphs($name, $indexName = null)
        {
            $this->string("{$name}_type");

            $this->unsignedBigInteger("{$name}_id");

            $this->index(["{$name}_type", "{$name}_id"], $indexName);
        }

        /**
         * Add nullable columns for a polymorphic table using numeric IDs (incremental).
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function nullableNumericMorphs($name, $indexName = null)
        {
            $this->string("{$name}_type")->nullable();

            $this->unsignedBigInteger("{$name}_id")->nullable();

            $this->index(["{$name}_type", "{$name}_id"], $indexName);
        }

        /**
         * Add the proper columns for a polymorphic table using UUIDs.
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function uuidMorphs($name, $indexName = null)
        {
            $this->string("{$name}_type");

            $this->uuid("{$name}_id");

            $this->index(["{$name}_type", "{$name}_id"], $indexName);
        }

        /**
         * Add nullable columns for a polymorphic table using UUIDs.
         *
         * @param string $name
         * @param string|null $indexName
         * @return void
         */
        public function nullableUuidMorphs($name, $indexName = null)
        {
            $this->string("{$name}_type")->nullable();

            $this->uuid("{$name}_id")->nullable();

            $this->index(["{$name}_type", "{$name}_id"], $indexName);
        }

        /**
         * Adds the `remember_token` column to the table.
         *
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function rememberToken()
        {
            return $this->string('remember_token', 100)->nullable();
        }

        /**
         * Add a new index command to the blueprint.
         *
         * @param string $type
         * @param string|array $columns
         * @param string $index
         * @param string|null $algorithm
         * @return \Illuminate\Support\Fluent
         */
        protected function indexCommand($type, $columns, $index, $algorithm = null)
        {
            $columns = (array)$columns;

            // If no name was specified for this index, we will create one using a basic
            // convention of the table name, followed by the columns, followed by an
            // index type, such as primary or index, which makes the index unique.
            $index = $index ?: $this->createIndexName($type, $columns);

            return $this->addCommand(
                $type, compact('index', 'columns', 'algorithm')
            );
        }

        /**
         * Create a new drop index command on the blueprint.
         *
         * @param string $command
         * @param string $type
         * @param string|array $index
         * @return \Illuminate\Support\Fluent
         */
        protected function dropIndexCommand($command, $type, $index)
        {
            $columns = [];

            // If the given "index" is actually an array of columns, the developer means
            // to drop an index merely by specifying the columns involved without the
            // conventional name, so we will build the index name from the columns.
            if (is_array($index)) {
                $index = $this->createIndexName($type, $columns = $index);
            }

            return $this->indexCommand($command, $columns, $index);
        }

        /**
         * Create a default index name for the table.
         *
         * @param string $type
         * @param array $columns
         * @return string
         */
        protected function createIndexName($type, array $columns)
        {
            $index = strtolower($this->prefix . $this->table . '_' . implode('_', $columns) . '_' . $type);

            return str_replace(['-', '.'], '_', $index);
        }

        /**
         * Add a new column to the blueprint.
         *
         * @param string $type
         * @param string $name
         * @param array $parameters
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        public function addColumn($type, $name, array $parameters = [])
        {
            return $this->addColumnDefinition(new ColumnDefinition(
                array_merge(compact('type', 'name'), $parameters)
            ));
        }

        /**
         * Add a new column definition to the blueprint.
         *
         * @param \Illuminate\Database\Schema\ColumnDefinition $definition
         * @return \Illuminate\Database\Schema\ColumnDefinition
         */
        protected function addColumnDefinition($definition)
        {
            $this->columns[] = $definition;

            if ($this->after) {
                $definition->after($this->after);

                $this->after = $definition->name;
            }

            return $definition;
        }

        /**
         * Add the columns from the callback after the given column.
         *
         * @param string $column
         * @param \Closure $callback
         * @return void
         */
        public function after($column, Closure $callback)
        {
            $this->after = $column;

            $callback($this);

            $this->after = null;
        }

        /**
         * Remove a column from the schema blueprint.
         *
         * @param string $name
         * @return $this
         */
        public function removeColumn($name)
        {
            $this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
                return $c['name'] != $name;
            }));

            return $this;
        }

        /**
         * Add a new command to the blueprint.
         *
         * @param string $name
         * @param array $parameters
         * @return \Illuminate\Support\Fluent
         */
        protected function addCommand($name, array $parameters = [])
        {
            $this->commands[] = $command = $this->createCommand($name, $parameters);

            return $command;
        }

        /**
         * Create a new Fluent command.
         *
         * @param string $name
         * @param array $parameters
         * @return \Illuminate\Support\Fluent
         */
        protected function createCommand($name, array $parameters = [])
        {
            return new Fluent(array_merge(compact('name'), $parameters));
        }

        /**
         * Get the table the blueprint describes.
         *
         * @return string
         */
        public function getTable()
        {
            return $this->table;
        }

        /**
         * Get the columns on the blueprint.
         *
         * @return \Illuminate\Database\Schema\ColumnDefinition[]
         */
        public function getColumns()
        {
            return $this->columns;
        }

        /**
         * Get the commands on the blueprint.
         *
         * @return \Illuminate\Support\Fluent[]
         */
        public function getCommands()
        {
            return $this->commands;
        }

        /**
         * Get the columns on the blueprint that should be added.
         *
         * @return \Illuminate\Database\Schema\ColumnDefinition[]
         */
        public function getAddedColumns()
        {
            return array_filter($this->columns, function ($column) {
                return !$column->change;
            });
        }

        /**
         * Get the columns on the blueprint that should be changed.
         *
         * @return \Illuminate\Database\Schema\ColumnDefinition[]
         */
        public function getChangedColumns()
        {
            return array_filter($this->columns, function ($column) {
                return (bool)$column->change;
            });
        }

        /**
         * Determine if the blueprint has auto-increment columns.
         *
         * @return bool
         */
        public function hasAutoIncrementColumn()
        {
            return !is_null(collect($this->getAddedColumns())->first(function ($column) {
                return $column->autoIncrement === true;
            }));
        }

        /**
         * Get the auto-increment column starting values.
         *
         * @return array
         */
        public function autoIncrementingStartingValues()
        {
            if (!$this->hasAutoIncrementColumn()) {
                return [];
            }

            return collect($this->getAddedColumns())->mapWithKeys(function ($column) {
                return $column->autoIncrement === true
                    ? [$column->name => $column->get('startingValue', $column->get('from'))]
                    : [$column->name => null];
            })->filter()->all();
        }
    }

}

/**
 * @property-read HigherOrderBuilderProxy $orWhere
 *
 * @mixin \Illuminate\Database\Query\Builder
 */

namespace Illuminate\Database\Eloquent {

    use Modules\Core\Entities\BaseModel;

    class Builder
    {
        use Concerns\QueriesRelationships, ExplainsQueries, ForwardsCalls;
        use BuildsQueries {
            sole as baseSole;
        }

        public function paginateOrAll($perPage = null, $columns = ['*'])
        {
            $perPage = request('per_page', $perPage);

            return request('all') ? $this->get($columns) : $this->paginate($perPage, $columns);
        }

        /**
         * @return Builder
         * @see BaseModel
         */
        public function dateFilter(): Builder
        {

        }

        /**
         * @return Builder
         * @see BaseModel
         */
        public function sortFilter(): Builder
        {

        }

        /**
         * @return Builder
         * @see BaseModel
         */
        public function searchFilters(): Builder
        {

        }

        /**
         * @return Builder
         * @see BaseModel
         */
        public function filters(): Builder
        {

        }

        /**
         * The base query builder instance.
         *
         * @var \Illuminate\Database\Query\Builder
         */
        protected $query;

        /**
         * The model being queried.
         *
         * @var \Illuminate\Database\Eloquent\Model
         */
        protected $model;

        /**
         * The relationships that should be eager loaded.
         *
         * @var array
         */
        protected $eagerLoad = [];

        /**
         * All of the globally registered builder macros.
         *
         * @var array
         */
        protected static $macros = [];

        /**
         * All of the locally registered builder macros.
         *
         * @var array
         */
        protected $localMacros = [];

        /**
         * A replacement for the typical delete function.
         *
         * @var \Closure
         */
        protected $onDelete;

        /**
         * The methods that should be returned from query builder.
         *
         * @var string[]
         */
        protected $passthru = [
            'average',
            'avg',
            'count',
            'dd',
            'doesntExist',
            'dump',
            'exists',
            'getBindings',
            'getConnection',
            'getGrammar',
            'insert',
            'insertGetId',
            'insertOrIgnore',
            'insertUsing',
            'max',
            'min',
            'raw',
            'sum',
            'toSql',
        ];

        /**
         * Applied global scopes.
         *
         * @var array
         */
        protected $scopes = [];

        /**
         * Removed global scopes.
         *
         * @var array
         */
        protected $removedScopes = [];

        /**
         * Create a new Eloquent query builder instance.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         * @return void
         */
        public function __construct(QueryBuilder $query)
        {
            $this->query = $query;
        }

        /**
         * Create and return an un-saved model instance.
         *
         * @param  array  $attributes
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function make(array $attributes = [])
        {
            return $this->newModelInstance($attributes);
        }

        /**
         * Register a new global scope.
         *
         * @param  string  $identifier
         * @param  \Illuminate\Database\Eloquent\Scope|\Closure  $scope
         * @return $this
         */
        public function withGlobalScope($identifier, $scope)
        {
            $this->scopes[$identifier] = $scope;

            if (method_exists($scope, 'extend')) {
                $scope->extend($this);
            }

            return $this;
        }

        /**
         * Remove a registered global scope.
         *
         * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
         * @return $this
         */
        public function withoutGlobalScope($scope)
        {
            if (! is_string($scope)) {
                $scope = get_class($scope);
            }

            unset($this->scopes[$scope]);

            $this->removedScopes[] = $scope;

            return $this;
        }

        /**
         * Remove all or passed registered global scopes.
         *
         * @param  array|null  $scopes
         * @return $this
         */
        public function withoutGlobalScopes(array $scopes = null)
        {
            if (! is_array($scopes)) {
                $scopes = array_keys($this->scopes);
            }

            foreach ($scopes as $scope) {
                $this->withoutGlobalScope($scope);
            }

            return $this;
        }

        /**
         * Get an array of global scopes that were removed from the query.
         *
         * @return array
         */
        public function removedScopes()
        {
            return $this->removedScopes;
        }

        /**
         * Add a where clause on the primary key to the query.
         *
         * @param  mixed  $id
         * @return $this
         */
        public function whereKey($id)
        {
            if (is_array($id) || $id instanceof Arrayable) {
                $this->query->whereIn($this->model->getQualifiedKeyName(), $id);

                return $this;
            }

            if ($id !== null && $this->model->getKeyType() === 'string') {
                $id = (string) $id;
            }

            return $this->where($this->model->getQualifiedKeyName(), '=', $id);
        }

        /**
         * Add a where clause on the primary key to the query.
         *
         * @param  mixed  $id
         * @return $this
         */
        public function whereKeyNot($id)
        {
            if (is_array($id) || $id instanceof Arrayable) {
                $this->query->whereNotIn($this->model->getQualifiedKeyName(), $id);

                return $this;
            }

            if ($id !== null && $this->model->getKeyType() === 'string') {
                $id = (string) $id;
            }

            return $this->where($this->model->getQualifiedKeyName(), '!=', $id);
        }

        /**
         * Add a basic where clause to the query.
         *
         * @param  \Closure|string|array|\Illuminate\Database\Query\Expression  $column
         * @param  mixed  $operator
         * @param  mixed  $value
         * @param  string  $boolean
         * @return $this
         */
        public function where($column, $operator = null, $value = null, $boolean = 'and')
        {
            if ($column instanceof Closure && is_null($operator)) {
//                $column($query = $this->model->newQueryWithoutRelationships());
            $query = '';
                $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
            } else {
                $this->query->where(...func_get_args());
            }

            return $this;
        }

        /**
         * Add a basic where clause to the query, and return the first result.
         *
         * @param  \Closure|string|array|\Illuminate\Database\Query\Expression  $column
         * @param  mixed  $operator
         * @param  mixed  $value
         * @param  string  $boolean
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function firstWhere($column, $operator = null, $value = null, $boolean = 'and')
        {
            return $this->where($column, $operator, $value, $boolean)->first();
        }

        /**
         * Add an "or where" clause to the query.
         *
         * @param  \Closure|array|string|\Illuminate\Database\Query\Expression  $column
         * @param  mixed  $operator
         * @param  mixed  $value
         * @return $this
         */
        public function orWhere($column, $operator = null, $value = null)
        {
            [$value, $operator] = $this->query->prepareValueAndOperator(
                $value, $operator, func_num_args() === 2
            );

            return $this->where($column, $operator, $value, 'or');
        }

        /**
         * Add an "order by" clause for a timestamp to the query.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @return $this
         */
        public function latest($column = null)
        {
            if (is_null($column)) {
                $column = $this->model->getCreatedAtColumn() ?? 'created_at';
            }

            $this->query->latest($column);

            return $this;
        }

        /**
         * Add an "order by" clause for a timestamp to the query.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @return $this
         */
        public function oldest($column = null)
        {
            if (is_null($column)) {
                $column = $this->model->getCreatedAtColumn() ?? 'created_at';
            }

            $this->query->oldest($column);

            return $this;
        }

        /**
         * Create a collection of models from plain arrays.
         *
         * @param  array  $items
         * @return \Illuminate\Database\Eloquent\Collection
         */
        public function hydrate(array $items)
        {
            $instance = $this->newModelInstance();

            return $instance->newCollection(array_map(function ($item) use ($instance) {
                return $instance->newFromBuilder($item);
            }, $items));
        }

        /**
         * Create a collection of models from a raw query.
         *
         * @param  string  $query
         * @param  array  $bindings
         * @return \Illuminate\Database\Eloquent\Collection
         */
        public function fromQuery($query, $bindings = [])
        {
            return $this->hydrate(
                $this->query->getConnection()->select($query, $bindings)
            );
        }

        /**
         * Find a model by its primary key.
         *
         * @param  mixed  $id
         * @param  array  $columns
         * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
         */
        public function find($id, $columns = ['*'])
        {
            if (is_array($id) || $id instanceof Arrayable) {
                return $this->findMany($id, $columns);
            }

            return $this->whereKey($id)->first($columns);
        }

        /**
         * Find multiple models by their primary keys.
         *
         * @param  \Illuminate\Contracts\Support\Arrayable|array  $ids
         * @param  array  $columns
         * @return \Illuminate\Database\Eloquent\Collection
         */
        public function findMany($ids, $columns = ['*'])
        {
            $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

            if (empty($ids)) {
                return $this->model->newCollection();
            }

            return $this->whereKey($ids)->get($columns);
        }

        /**
         * Find a model by its primary key or throw an exception.
         *
         * @param  mixed  $id
         * @param  array  $columns
         * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static|static[]
         *
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         */
        public function findOrFail($id, $columns = ['*'])
        {
            $result = $this->find($id, $columns);

            $id = $id instanceof Arrayable ? $id->toArray() : $id;

            if (is_array($id)) {
                if (count($result) === count(array_unique($id))) {
                    return $result;
                }
            } elseif (! is_null($result)) {
                return $result;
            }

            throw (new ModelNotFoundException)->setModel(
                get_class($this->model), $id
            );
        }

        /**
         * Find a model by its primary key or return fresh model instance.
         *
         * @param  mixed  $id
         * @param  array  $columns
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function findOrNew($id, $columns = ['*'])
        {
            if (! is_null($model = $this->find($id, $columns))) {
                return $model;
            }

            return $this->newModelInstance();
        }

        /**
         * Get the first record matching the attributes or instantiate it.
         *
         * @param  array  $attributes
         * @param  array  $values
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function firstOrNew(array $attributes = [], array $values = [])
        {
            if (! is_null($instance = $this->where($attributes)->first())) {
                return $instance;
            }

            return $this->newModelInstance($attributes + $values);
        }

        /**
         * Get the first record matching the attributes or create it.
         *
         * @param  array  $attributes
         * @param  array  $values
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function firstOrCreate(array $attributes = [], array $values = [])
        {
            if (! is_null($instance = $this->where($attributes)->first())) {
                return $instance;
            }

            return tap($this->newModelInstance($attributes + $values), function ($instance) {
                $instance->save();
            });
        }

        /**
         * Create or update a record matching the attributes, and fill it with values.
         *
         * @param  array  $attributes
         * @param  array  $values
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function updateOrCreate(array $attributes, array $values = [])
        {
            return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
                $instance->fill($values)->save();
            });
        }

        /**
         * Execute the query and get the first result or throw an exception.
         *
         * @param  array  $columns
         * @return \Illuminate\Database\Eloquent\Model|static
         *
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         */
        public function firstOrFail($columns = ['*'])
        {
            if (! is_null($model = $this->first($columns))) {
                return $model;
            }

            throw (new ModelNotFoundException)->setModel(get_class($this->model));
        }

        /**
         * Execute the query and get the first result or call a callback.
         *
         * @param  \Closure|array  $columns
         * @param  \Closure|null  $callback
         * @return \Illuminate\Database\Eloquent\Model|static|mixed
         */
        public function firstOr($columns = ['*'], Closure $callback = null)
        {
            if ($columns instanceof Closure) {
                $callback = $columns;

                $columns = ['*'];
            }

            if (! is_null($model = $this->first($columns))) {
                return $model;
            }

            return $callback();
        }

        /**
         * Execute the query and get the first result if it's the sole matching record.
         *
         * @param  array|string  $columns
         * @return \Illuminate\Database\Eloquent\Model
         *
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @throws \Illuminate\Database\MultipleRecordsFoundException
         */
        public function sole($columns = ['*'])
        {
            try {
                return $this->baseSole($columns);
            } catch (RecordsNotFoundException $exception) {
                throw (new ModelNotFoundException)->setModel(get_class($this->model));
            }
        }

        /**
         * Get a single column's value from the first result of a query.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @return mixed
         */
        public function value($column)
        {
            if ($result = $this->first([$column])) {
                return $result->{Str::afterLast($column, '.')};
            }
        }

        /**
         * Execute the query as a "select" statement.
         *
         * @param  array|string  $columns
         * @return \Illuminate\Database\Eloquent\Collection|static[]
         */
        public function get($columns = ['*'])
        {
            $builder = $this->applyScopes();

            // If we actually found models we will also eager load any relationships that
            // have been specified as needing to be eager loaded, which will solve the
            // n+1 query issue for the developers to avoid running a lot of queries.
            if (count($models = $builder->getModels($columns)) > 0) {
                $models = $builder->eagerLoadRelations($models);
            }

            return $builder->getModel()->newCollection($models);
        }

        /**
         * Get the hydrated models without eager loading.
         *
         * @param  array|string  $columns
         * @return \Illuminate\Database\Eloquent\Model[]|static[]
         */
        public function getModels($columns = ['*'])
        {
            return $this->model->hydrate(
                $this->query->get($columns)->all()
            )->all();
        }

        /**
         * Eager load the relationships for the models.
         *
         * @param  array  $models
         * @return array
         */
        public function eagerLoadRelations(array $models)
        {
            foreach ($this->eagerLoad as $name => $constraints) {
                // For nested eager loads we'll skip loading them here and they will be set as an
                // eager load on the query to retrieve the relation so that they will be eager
                // loaded on that query, because that is where they get hydrated as models.
                if (strpos($name, '.') === false) {
                    $models = $this->eagerLoadRelation($models, $name, $constraints);
                }
            }

            return $models;
        }

        /**
         * Eagerly load the relationship on a set of models.
         *
         * @param  array  $models
         * @param  string  $name
         * @param  \Closure  $constraints
         * @return array
         */
        protected function eagerLoadRelation(array $models, $name, Closure $constraints)
        {
            // First we will "back up" the existing where conditions on the query so we can
            // add our eager constraints. Then we will merge the wheres that were on the
            // query back to it in order that any where conditions might be specified.
            $relation = $this->getRelation($name);

            $relation->addEagerConstraints($models);

            $constraints($relation);

            // Once we have the results, we just match those back up to their parent models
            // using the relationship instance. Then we just return the finished arrays
            // of models which have been eagerly hydrated and are readied for return.
            return $relation->match(
                $relation->initRelation($models, $name),
                $relation->getEager(), $name
            );
        }

        /**
         * Get the relation instance for the given relation name.
         *
         * @param  string  $name
         * @return \Illuminate\Database\Eloquent\Relations\Relation
         */
        public function getRelation($name)
        {
            // We want to run a relationship query without any constrains so that we will
            // not have to remove these where clauses manually which gets really hacky
            // and error prone. We don't want constraints because we add eager ones.
            $relation = Relation::noConstraints(function () use ($name) {
                try {
                    return $this->getModel()->newInstance()->$name();
                } catch (BadMethodCallException $e) {
                    throw RelationNotFoundException::make($this->getModel(), $name);
                }
            });

            $nested = $this->relationsNestedUnder($name);

            // If there are nested relationships set on the query, we will put those onto
            // the query instances so that they can be handled after this relationship
            // is loaded. In this way they will all trickle down as they are loaded.
            if (count($nested) > 0) {
                $relation->getQuery()->with($nested);
            }

            return $relation;
        }

        /**
         * Get the deeply nested relations for a given top-level relation.
         *
         * @param  string  $relation
         * @return array
         */
        protected function relationsNestedUnder($relation)
        {
            $nested = [];

            // We are basically looking for any relationships that are nested deeper than
            // the given top-level relationship. We will just check for any relations
            // that start with the given top relations and adds them to our arrays.
            foreach ($this->eagerLoad as $name => $constraints) {
                if ($this->isNestedUnder($relation, $name)) {
                    $nested[substr($name, strlen($relation.'.'))] = $constraints;
                }
            }

            return $nested;
        }

        /**
         * Determine if the relationship is nested.
         *
         * @param  string  $relation
         * @param  string  $name
         * @return bool
         */
        protected function isNestedUnder($relation, $name)
        {
            return Str::contains($name, '.') && Str::startsWith($name, $relation.'.');
        }

        /**
         * Get a lazy collection for the given query.
         *
         * @return \Illuminate\Support\LazyCollection
         */
        public function cursor()
        {
            return $this->applyScopes()->query->cursor()->map(function ($record) {
                return $this->newModelInstance()->newFromBuilder($record);
            });
        }

        /**
         * Add a generic "order by" clause if the query doesn't already have one.
         *
         * @return void
         */
        protected function enforceOrderBy()
        {
            if (empty($this->query->orders) && empty($this->query->unionOrders)) {
                $this->orderBy($this->model->getQualifiedKeyName(), 'asc');
            }
        }

        /**
         * Get an array with the values of a given column.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @param  string|null  $key
         * @return \Illuminate\Support\Collection
         */
        public function pluck($column, $key = null)
        {
            $results = $this->toBase()->pluck($column, $key);

            // If the model has a mutator for the requested column, we will spin through
            // the results and mutate the values so that the mutated version of these
            // columns are returned as you would expect from these Eloquent models.
            if (! $this->model->hasGetMutator($column) &&
                ! $this->model->hasCast($column) &&
                ! in_array($column, $this->model->getDates())) {
                return $results;
            }

            return $results->map(function ($value) use ($column) {
                return $this->model->newFromBuilder([$column => $value])->{$column};
            });
        }

        /**
         * Paginate the given query.
         *
         * @param  int|null  $perPage
         * @param  array  $columns
         * @param  string  $pageName
         * @param  int|null  $page
         * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
         *
         * @throws \InvalidArgumentException
         */
        public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
        {
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $perPage = $perPage ?: $this->model->getPerPage();

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $perPage)->get($columns)
                : $this->model->newCollection();

            return $this->paginator($results, $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        }

        /**
         * Paginate the given query into a simple paginator.
         *
         * @param  int|null  $perPage
         * @param  array  $columns
         * @param  string  $pageName
         * @param  int|null  $page
         * @return \Illuminate\Contracts\Pagination\Paginator
         */
        public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
        {
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $perPage = $perPage ?: $this->model->getPerPage();

            // Next we will set the limit and offset for this query so that when we get the
            // results we get the proper section of results. Then, we'll create the full
            // paginator instances for these results with the given page and per page.
            $this->skip(($page - 1) * $perPage)->take($perPage + 1);

            return $this->simplePaginator($this->get($columns), $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        }

        /**
         * Save a new model and return the instance.
         *
         * @param  array  $attributes
         * @return \Illuminate\Database\Eloquent\Model|$this
         */
        public function create(array $attributes = [])
        {
            return tap($this->newModelInstance($attributes), function ($instance) {
                $instance->save();
            });
        }

        /**
         * Save a new model and return the instance. Allow mass-assignment.
         *
         * @param  array  $attributes
         * @return \Illuminate\Database\Eloquent\Model|$this
         */
        public function forceCreate(array $attributes)
        {
            return $this->model->unguarded(function () use ($attributes) {
                return $this->newModelInstance()->create($attributes);
            });
        }

        /**
         * Update records in the database.
         *
         * @param  array  $values
         * @return int
         */
        public function update(array $values)
        {
            return $this->toBase()->update($this->addUpdatedAtColumn($values));
        }

        /**
         * Insert new records or update the existing ones.
         *
         * @param  array  $values
         * @param  array|string  $uniqueBy
         * @param  array|null  $update
         * @return int
         */
        public function upsert(array $values, $uniqueBy, $update = null)
        {
            if (empty($values)) {
                return 0;
            }

            if (! is_array(reset($values))) {
                $values = [$values];
            }

            if (is_null($update)) {
                $update = array_keys(reset($values));
            }

            return $this->toBase()->upsert(
                $this->addTimestampsToUpsertValues($values),
                $uniqueBy,
                $this->addUpdatedAtToUpsertColumns($update)
            );
        }

        /**
         * Increment a column's value by a given amount.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @param  float|int  $amount
         * @param  array  $extra
         * @return int
         */
        public function increment($column, $amount = 1, array $extra = [])
        {
            return $this->toBase()->increment(
                $column, $amount, $this->addUpdatedAtColumn($extra)
            );
        }

        /**
         * Decrement a column's value by a given amount.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @param  float|int  $amount
         * @param  array  $extra
         * @return int
         */
        public function decrement($column, $amount = 1, array $extra = [])
        {
            return $this->toBase()->decrement(
                $column, $amount, $this->addUpdatedAtColumn($extra)
            );
        }

        /**
         * Add the "updated at" column to an array of values.
         *
         * @param  array  $values
         * @return array
         */
        protected function addUpdatedAtColumn(array $values)
        {
            if (! $this->model->usesTimestamps() ||
                is_null($this->model->getUpdatedAtColumn())) {
                return $values;
            }

            $column = $this->model->getUpdatedAtColumn();

            $values = array_merge(
                [$column => $this->model->freshTimestampString()],
                $values
            );

            $segments = preg_split('/\s+as\s+/i', $this->query->from);

            $qualifiedColumn = end($segments).'.'.$column;

            $values[$qualifiedColumn] = $values[$column];

            unset($values[$column]);

            return $values;
        }

        /**
         * Add timestamps to the inserted values.
         *
         * @param  array  $values
         * @return array
         */
        protected function addTimestampsToUpsertValues(array $values)
        {
            if (! $this->model->usesTimestamps()) {
                return $values;
            }

            $timestamp = $this->model->freshTimestampString();

            $columns = array_filter([
                $this->model->getCreatedAtColumn(),
                $this->model->getUpdatedAtColumn(),
            ]);

            foreach ($columns as $column) {
                foreach ($values as &$row) {
                    $row = array_merge([$column => $timestamp], $row);
                }
            }

            return $values;
        }

        /**
         * Add the "updated at" column to the updated columns.
         *
         * @param  array  $update
         * @return array
         */
        protected function addUpdatedAtToUpsertColumns(array $update)
        {
            if (! $this->model->usesTimestamps()) {
                return $update;
            }

            $column = $this->model->getUpdatedAtColumn();

            if (! is_null($column) &&
                ! array_key_exists($column, $update) &&
                ! in_array($column, $update)) {
                $update[] = $column;
            }

            return $update;
        }

        /**
         * Delete records from the database.
         *
         * @return mixed
         */
        public function delete()
        {
            if (isset($this->onDelete)) {
                return call_user_func($this->onDelete, $this);
            }

            return $this->toBase()->delete();
        }

        /**
         * Run the default delete function on the builder.
         *
         * Since we do not apply scopes here, the row will actually be deleted.
         *
         * @return mixed
         */
        public function forceDelete()
        {
            return $this->query->delete();
        }

        /**
         * Register a replacement for the default delete function.
         *
         * @param  \Closure  $callback
         * @return void
         */
        public function onDelete(Closure $callback)
        {
            $this->onDelete = $callback;
        }

        /**
         * Determine if the given model has a scope.
         *
         * @param  string  $scope
         * @return bool
         */
        public function hasNamedScope($scope)
        {
            return $this->model && $this->model->hasNamedScope($scope);
        }

        /**
         * Call the given local model scopes.
         *
         * @param  array|string  $scopes
         * @return static|mixed
         */
        public function scopes($scopes)
        {
            $builder = $this;

            foreach (Arr::wrap($scopes) as $scope => $parameters) {
                // If the scope key is an integer, then the scope was passed as the value and
                // the parameter list is empty, so we will format the scope name and these
                // parameters here. Then, we'll be ready to call the scope on the model.
                if (is_int($scope)) {
                    [$scope, $parameters] = [$parameters, []];
                }

                // Next we'll pass the scope callback to the callScope method which will take
                // care of grouping the "wheres" properly so the logical order doesn't get
                // messed up when adding scopes. Then we'll return back out the builder.
                $builder = $builder->callNamedScope($scope, (array) $parameters);
            }

            return $builder;
        }

        /**
         * Apply the scopes to the Eloquent builder instance and return it.
         *
         * @return static
         */
        public function applyScopes()
        {
            if (! $this->scopes) {
                return $this;
            }

            $builder = clone $this;

            foreach ($this->scopes as $identifier => $scope) {
                if (! isset($builder->scopes[$identifier])) {
                    continue;
                }

                $builder->callScope(function (self $builder) use ($scope) {
                    // If the scope is a Closure we will just go ahead and call the scope with the
                    // builder instance. The "callScope" method will properly group the clauses
                    // that are added to this query so "where" clauses maintain proper logic.
                    if ($scope instanceof Closure) {
                        asdasdas($builder);
                    }

                    // If the scope is a scope object, we will call the apply method on this scope
                    // passing in the builder and the model instance. After we run all of these
                    // scopes we will return back the builder instance to the outside caller.
                    if ($scope instanceof Scope) {
                        $scope->apply($builder, $this->getModel());
                    }
                });
            }

            return $builder;
        }

        /**
         * Apply the given scope on the current builder instance.
         *
         * @param  callable  $scope
         * @param  array  $parameters
         * @return mixed
         */
        protected function callScope(callable $scope, array $parameters = [])
        {
            array_unshift($parameters, $this);

            $query = $this->getQuery();

            // We will keep track of how many wheres are on the query before running the
            // scope so that we can properly group the added scope constraints in the
            // query as their own isolated nested where statement and avoid issues.
            $originalWhereCount = is_null($query->wheres)
                ? 0 : count($query->wheres);

            $result = $scope(...array_values($parameters)) ?? $this;

            if (count((array) $query->wheres) > $originalWhereCount) {
                $this->addNewWheresWithinGroup($query, $originalWhereCount);
            }

            return $result;
        }

        /**
         * Apply the given named scope on the current builder instance.
         *
         * @param  string  $scope
         * @param  array  $parameters
         * @return mixed
         */
        protected function callNamedScope($scope, array $parameters = [])
        {
            return $this->callScope(function (...$parameters) use ($scope) {
                return $this->model->callNamedScope($scope, $parameters);
            }, $parameters);
        }

        /**
         * Nest where conditions by slicing them at the given where count.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         * @param  int  $originalWhereCount
         * @return void
         */
        protected function addNewWheresWithinGroup(QueryBuilder $query, $originalWhereCount)
        {
            // Here, we totally remove all of the where clauses since we are going to
            // rebuild them as nested queries by slicing the groups of wheres into
            // their own sections. This is to prevent any confusing logic order.
            $allWheres = $query->wheres;

            $query->wheres = [];

            $this->groupWhereSliceForScope(
                $query, array_slice($allWheres, 0, $originalWhereCount)
            );

            $this->groupWhereSliceForScope(
                $query, array_slice($allWheres, $originalWhereCount)
            );
        }

        /**
         * Slice where conditions at the given offset and add them to the query as a nested condition.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         * @param  array  $whereSlice
         * @return void
         */
        protected function groupWhereSliceForScope(QueryBuilder $query, $whereSlice)
        {
            $whereBooleans = collect($whereSlice)->pluck('boolean');

            // Here we'll check if the given subset of where clauses contains any "or"
            // booleans and in this case create a nested where expression. That way
            // we don't add any unnecessary nesting thus keeping the query clean.
            if ($whereBooleans->contains('or')) {
                $query->wheres[] = $this->createNestedWhere(
                    $whereSlice, $whereBooleans->first()
                );
            } else {
                $query->wheres = array_merge($query->wheres, $whereSlice);
            }
        }

        /**
         * Create a where array with nested where conditions.
         *
         * @param  array  $whereSlice
         * @param  string  $boolean
         * @return array
         */
        protected function createNestedWhere($whereSlice, $boolean = 'and')
        {
            $whereGroup = $this->getQuery()->forNestedWhere();

            $whereGroup->wheres = $whereSlice;

            return ['type' => 'Nested', 'query' => $whereGroup, 'boolean' => $boolean];
        }

        /**
         * Set the relationships that should be eager loaded.
         *
         * @param  string|array  $relations
         * @param  string|\Closure|null  $callback
         * @return $this
         */
        public function with($relations, $callback = null)
        {
            if ($callback instanceof Closure) {
                $eagerLoad = $this->parseWithRelations([$relations => $callback]);
            } else {
                $eagerLoad = $this->parseWithRelations(is_string($relations) ? func_get_args() : $relations);
            }

            $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

            return $this;
        }

        /**
         * Prevent the specified relations from being eager loaded.
         *
         * @param  mixed  $relations
         * @return $this
         */
        public function without($relations)
        {
            $this->eagerLoad = array_diff_key($this->eagerLoad, array_flip(
                is_string($relations) ? func_get_args() : $relations
            ));

            return $this;
        }

        /**
         * Create a new instance of the model being queried.
         *
         * @param  array  $attributes
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function newModelInstance($attributes = [])
        {
            return $this->model->newInstance($attributes)->setConnection(
                $this->query->getConnection()->getName()
            );
        }

        /**
         * Parse a list of relations into individuals.
         *
         * @param  array  $relations
         * @return array
         */
        protected function parseWithRelations(array $relations)
        {
            $results = [];

            foreach ($relations as $name => $constraints) {
                // If the "name" value is a numeric key, we can assume that no constraints
                // have been specified. We will just put an empty Closure there so that
                // we can treat these all the same while we are looping through them.
                if (is_numeric($name)) {
                    $name = $constraints;

                    [$name, $constraints] = Str::contains($name, ':')
                        ? $this->createSelectWithConstraint($name)
                        : [$name, static function () {
                            //
                        }];
                }

                // We need to separate out any nested includes, which allows the developers
                // to load deep relationships using "dots" without stating each level of
                // the relationship with its own key in the array of eager-load names.
                $results = $this->addNestedWiths($name, $results);

                $results[$name] = $constraints;
            }

            return $results;
        }

        /**
         * Create a constraint to select the given columns for the relation.
         *
         * @param  string  $name
         * @return array
         */
        protected function createSelectWithConstraint($name)
        {
            return [explode(':', $name)[0], static function ($query) use ($name) {
                $query->select(array_map(static function ($column) use ($query) {
                    if (Str::contains($column, '.')) {
                        return $column;
                    }

                    return $query instanceof BelongsToMany
                        ? $query->getRelated()->getTable().'.'.$column
                        : $column;
                }, explode(',', explode(':', $name)[1])));
            }];
        }

        /**
         * Parse the nested relationships in a relation.
         *
         * @param  string  $name
         * @param  array  $results
         * @return array
         */
        protected function addNestedWiths($name, $results)
        {
            $progress = [];

            // If the relation has already been set on the result array, we will not set it
            // again, since that would override any constraints that were already placed
            // on the relationships. We will only set the ones that are not specified.
            foreach (explode('.', $name) as $segment) {
                $progress[] = $segment;

                if (! isset($results[$last = implode('.', $progress)])) {
                    $results[$last] = static function () {
                        //
                    };
                }
            }

            return $results;
        }

        /**
         * Apply query-time casts to the model instance.
         *
         * @param  array  $casts
         * @return $this
         */
        public function withCasts($casts)
        {
            $this->model->mergeCasts($casts);

            return $this;
        }

        /**
         * Get the underlying query builder instance.
         *
         * @return \Illuminate\Database\Query\Builder
         */
        public function getQuery()
        {
            return $this->query;
        }

        /**
         * Set the underlying query builder instance.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         * @return $this
         */
        public function setQuery($query)
        {
            $this->query = $query;

            return $this;
        }

        /**
         * Get a base query builder instance.
         *
         * @return \Illuminate\Database\Query\Builder
         */
        public function toBase()
        {
            return $this->applyScopes()->getQuery();
        }

        /**
         * Get the relationships being eagerly loaded.
         *
         * @return array
         */
        public function getEagerLoads()
        {
            return $this->eagerLoad;
        }

        /**
         * Set the relationships being eagerly loaded.
         *
         * @param  array  $eagerLoad
         * @return $this
         */
        public function setEagerLoads(array $eagerLoad)
        {
            $this->eagerLoad = $eagerLoad;

            return $this;
        }

        /**
         * Get the default key name of the table.
         *
         * @return string
         */
        protected function defaultKeyName()
        {
            return $this->getModel()->getKeyName();
        }

        /**
         * Get the model instance being queried.
         *
         * @return \Illuminate\Database\Eloquent\Model|static
         */
        public function getModel()
        {
            return $this->model;
        }

        /**
         * Set a model instance for the model being queried.
         *
         * @param  \Illuminate\Database\Eloquent\Model  $model
         * @return $this
         */
        public function setModel(Model $model)
        {
            $this->model = $model;

            $this->query->from($model->getTable());

            return $this;
        }

        /**
         * Qualify the given column name by the model's table.
         *
         * @param  string|\Illuminate\Database\Query\Expression  $column
         * @return string
         */
        public function qualifyColumn($column)
        {
            return $this->model->qualifyColumn($column);
        }

        /**
         * Get the given macro by name.
         *
         * @param  string  $name
         * @return \Closure
         */
        public function getMacro($name)
        {
            return Arr::get($this->localMacros, $name);
        }

        /**
         * Checks if a macro is registered.
         *
         * @param  string  $name
         * @return bool
         */
        public function hasMacro($name)
        {
            return isset($this->localMacros[$name]);
        }

        /**
         * Get the given global macro by name.
         *
         * @param  string  $name
         * @return \Closure
         */
        public static function getGlobalMacro($name)
        {
            return Arr::get(static::$macros, $name);
        }

        /**
         * Checks if a global macro is registered.
         *
         * @param  string  $name
         * @return bool
         */
        public static function hasGlobalMacro($name)
        {
            return isset(static::$macros[$name]);
        }

        /**
         * Dynamically access builder proxies.
         *
         * @param  string  $key
         * @return mixed
         *
         * @throws \Exception
         */
        public function __get($key)
        {
            if ($key === 'orWhere') {
                return new HigherOrderBuilderProxy($this, $key);
            }

            throw new Exception("Property [{$key}] does not exist on the Eloquent builder instance.");
        }

        /**
         * Dynamically handle calls into the query instance.
         *
         * @param  string  $method
         * @param  array  $parameters
         * @return mixed
         */
        public function __call($method, $parameters)
        {
            if ($method === 'macro') {
                $this->localMacros[$parameters[0]] = $parameters[1];

                return;
            }

            if ($this->hasMacro($method)) {
                array_unshift($parameters, $this);

                return $this->localMacros[$method](...$parameters);
            }

            if (static::hasGlobalMacro($method)) {
                $callable = static::$macros[$method];

                if ($callable instanceof Closure) {
                    $callable = $callable->bindTo($this, static::class);
                }

                return $callable(...$parameters);
            }

            if ($this->hasNamedScope($method)) {
                return $this->callNamedScope($method, $parameters);
            }

            if (in_array($method, $this->passthru)) {
                return $this->toBase()->{$method}(...$parameters);
            }

            $this->forwardCallTo($this->query, $method, $parameters);

            return $this;
        }

        /**
         * Dynamically handle calls into the query instance.
         *
         * @param  string  $method
         * @param  array  $parameters
         * @return mixed
         *
         * @throws \BadMethodCallException
         */
        public static function __callStatic($method, $parameters)
        {
            if ($method === 'macro') {
                static::$macros[$parameters[0]] = $parameters[1];

                return;
            }

            if ($method === 'mixin') {
                return static::registerMixin($parameters[0], $parameters[1] ?? true);
            }

            if (! static::hasGlobalMacro($method)) {
                static::throwBadMethodCallException($method);
            }

            $callable = static::$macros[$method];

            if ($callable instanceof Closure) {
                $callable = $callable->bindTo(null, static::class);
            }

            return $callable(...$parameters);
        }

        /**
         * Register the given mixin with the builder.
         *
         * @param  string  $mixin
         * @param  bool  $replace
         * @return void
         */
        protected static function registerMixin($mixin, $replace)
        {
            $methods = (new ReflectionClass($mixin))->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
            );

            foreach ($methods as $method) {
                if ($replace || ! static::hasGlobalMacro($method->name)) {
                    $method->setAccessible(true);

                    static::macro($method->name, $method->invoke($mixin));
                }
            }
        }

        /**
         * Force a clone of the underlying query builder when cloning.
         *
         * @return void
         */
        public function __clone()
        {
            $this->query = clone $this->query;
        }
    }

}

namespace Illuminate\Support\Facades {
    class Route {
        public function superGroup($model, $callback, $middleware = [])
        {

        }


        /**
         * Register a new GET route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function get($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->get($uri, $action);
        }
        /**
         * Register a new POST route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function post($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->post($uri, $action);
        }
        /**
         * Register a new PUT route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function put($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->put($uri, $action);
        }
        /**
         * Register a new PATCH route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function patch($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->patch($uri, $action);
        }
        /**
         * Register a new DELETE route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function delete($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->delete($uri, $action);
        }
        /**
         * Register a new OPTIONS route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function options($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->options($uri, $action);
        }
        /**
         * Register a new route responding to all verbs.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function any($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->any($uri, $action);
        }
        /**
         * Register a new Fallback route with the router.
         *
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function fallback($action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->fallback($action);
        }
        /**
         * Create a redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @param int $status
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function redirect($uri, $destination, $status = 302)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->redirect($uri, $destination, $status);
        }
        /**
         * Create a permanent redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function permanentRedirect($uri, $destination)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->permanentRedirect($uri, $destination);
        }
        /**
         * Register a new route that returns a view.
         *
         * @param string $uri
         * @param string $view
         * @param array $data
         * @param int|array $status
         * @param array $headers
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function view($uri, $view, $data = [], $status = 200, $headers = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->view($uri, $view, $data, $status, $headers);
        }
        /**
         * Register a new route with the given verbs.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function match($methods, $uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->match($methods, $uri, $action);
        }
        /**
         * Register an array of resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void
         * @static
         */
        public static function resources($resources, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->resources($resources, $options);
        }
        /**
         * Route a resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration
         * @static
         */
        public static function resource($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->resource($name, $controller, $options);
        }
        /**
         * Register an array of API resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void
         * @static
         */
        public static function apiResources($resources, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->apiResources($resources, $options);
        }
        /**
         * Route an API resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration
         * @static
         */
        public static function apiResource($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->apiResource($name, $controller, $options);
        }
        /**
         * Create a route group with shared attributes.
         *
         * @param array $attributes
         * @param \Closure|string $routes
         * @return void
         * @static
         */
        public static function group($attributes, $routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->group($attributes, $routes);
        }
        /**
         * Merge the given array with the last group stack.
         *
         * @param array $new
         * @param bool $prependExistingPrefix
         * @return array
         * @static
         */
        public static function mergeWithLastGroup($new, $prependExistingPrefix = true)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->mergeWithLastGroup($new, $prependExistingPrefix);
        }
        /**
         * Get the prefix from the last group on the stack.
         *
         * @return string
         * @static
         */
        public static function getLastGroupPrefix()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getLastGroupPrefix();
        }
        /**
         * Add a route to the underlying route collection.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function addRoute($methods, $uri, $action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->addRoute($methods, $uri, $action);
        }
        /**
         * Create a new Route object.
         *
         * @param array|string $methods
         * @param string $uri
         * @param mixed $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function newRoute($methods, $uri, $action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->newRoute($methods, $uri, $action);
        }
        /**
         * Return the response returned by the given route.
         *
         * @param string $name
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function respondWithRoute($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->respondWithRoute($name);
        }
        /**
         * Dispatch the request to the application.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function dispatch($request)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->dispatch($request);
        }
        /**
         * Dispatch the request to a route and return the response.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function dispatchToRoute($request)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->dispatchToRoute($request);
        }
        /**
         * Gather the middleware for the given route with resolved class names.
         *
         * @param \Illuminate\Routing\Route $route
         * @return array
         * @static
         */
        public static function gatherRouteMiddleware($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->gatherRouteMiddleware($route);
        }
        /**
         * Create a response instance from the given value.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function prepareResponse($request, $response)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->prepareResponse($request, $response);
        }
        /**
         * Static version of prepareResponse.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function toResponse($request, $response)
        {
            return \Illuminate\Routing\Router::toResponse($request, $response);
        }
        /**
         * Substitute the route bindings onto the route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return \Illuminate\Routing\Route
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static
         */
        public static function substituteBindings($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->substituteBindings($route);
        }
        /**
         * Substitute the implicit Eloquent model bindings for the route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return void
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static
         */
        public static function substituteImplicitBindings($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->substituteImplicitBindings($route);
        }
        /**
         * Register a route matched event listener.
         *
         * @param string|callable $callback
         * @return void
         * @static
         */
        public static function matched($callback)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->matched($callback);
        }
        /**
         * Get all of the defined middleware short-hand names.
         *
         * @return array
         * @static
         */
        public static function getMiddleware()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getMiddleware();
        }
        /**
         * Register a short-hand name for a middleware.
         *
         * @param string $name
         * @param string $class
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function aliasMiddleware($name, $class)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->aliasMiddleware($name, $class);
        }
        /**
         * Check if a middlewareGroup with the given name exists.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMiddlewareGroup($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->hasMiddlewareGroup($name);
        }
        /**
         * Get all of the defined middleware groups.
         *
         * @return array
         * @static
         */
        public static function getMiddlewareGroups()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getMiddlewareGroups();
        }
        /**
         * Register a group of middleware.
         *
         * @param string $name
         * @param array $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function middlewareGroup($name, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->middlewareGroup($name, $middleware);
        }
        /**
         * Add a middleware to the beginning of a middleware group.
         *
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function prependMiddlewareToGroup($group, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->prependMiddlewareToGroup($group, $middleware);
        }
        /**
         * Add a middleware to the end of a middleware group.
         *
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function pushMiddlewareToGroup($group, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->pushMiddlewareToGroup($group, $middleware);
        }
        /**
         * Flush the router's middleware groups.
         *
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function flushMiddlewareGroups()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->flushMiddlewareGroups();
        }
        /**
         * Add a new route parameter binder.
         *
         * @param string $key
         * @param string|callable $binder
         * @return void
         * @static
         */
        public static function bind($key, $binder)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->bind($key, $binder);
        }
        /**
         * Register a model binder for a wildcard.
         *
         * @param string $key
         * @param string $class
         * @param \Closure|null $callback
         * @return void
         * @static
         */
        public static function model($key, $class, $callback = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->model($key, $class, $callback);
        }
        /**
         * Get the binding callback for a given binding.
         *
         * @param string $key
         * @return \Closure|null
         * @static
         */
        public static function getBindingCallback($key)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getBindingCallback($key);
        }
        /**
         * Get the global "where" patterns.
         *
         * @return array
         * @static
         */
        public static function getPatterns()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getPatterns();
        }
        /**
         * Set a global where pattern on all routes.
         *
         * @param string $key
         * @param string $pattern
         * @return void
         * @static
         */
        public static function pattern($key, $pattern)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->pattern($key, $pattern);
        }
        /**
         * Set a group of global where patterns on all routes.
         *
         * @param array $patterns
         * @return void
         * @static
         */
        public static function patterns($patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->patterns($patterns);
        }
        /**
         * Determine if the router currently has a group stack.
         *
         * @return bool
         * @static
         */
        public static function hasGroupStack()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->hasGroupStack();
        }
        /**
         * Get the current group stack for the router.
         *
         * @return array
         * @static
         */
        public static function getGroupStack()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getGroupStack();
        }
        /**
         * Get a route parameter for the current route.
         *
         * @param string $key
         * @param string|null $default
         * @return mixed
         * @static
         */
        public static function input($key, $default = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->input($key, $default);
        }
        /**
         * Get the request currently being dispatched.
         *
         * @return \Illuminate\Http\Request
         * @static
         */
        public static function getCurrentRequest()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getCurrentRequest();
        }
        /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null
         * @static
         */
        public static function getCurrentRoute()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getCurrentRoute();
        }
        /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null
         * @static
         */
        public static function current()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->current();
        }
        /**
         * Check if a route with the given name exists.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function has($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->has($name);
        }
        /**
         * Get the current route name.
         *
         * @return string|null
         * @static
         */
        public static function currentRouteName()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteName();
        }
        /**
         * Alias for the "currentRouteNamed" method.
         *
         * @param mixed $patterns
         * @return bool
         * @static
         */
        public static function is(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->is(...$patterns);
        }
        /**
         * Determine if the current route matches a pattern.
         *
         * @param mixed $patterns
         * @return bool
         * @static
         */
        public static function currentRouteNamed(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteNamed(...$patterns);
        }
        /**
         * Get the current route action.
         *
         * @return string|null
         * @static
         */
        public static function currentRouteAction()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteAction();
        }
        /**
         * Alias for the "currentRouteUses" method.
         *
         * @param array $patterns
         * @return bool
         * @static
         */
        public static function uses(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->uses(...$patterns);
        }
        /**
         * Determine if the current route action matches a given action.
         *
         * @param string $action
         * @return bool
         * @static
         */
        public static function currentRouteUses($action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteUses($action);
        }
        /**
         * Set the unmapped global resource parameters to singular.
         *
         * @param bool $singular
         * @return void
         * @static
         */
        public static function singularResourceParameters($singular = true)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->singularResourceParameters($singular);
        }
        /**
         * Set the global resource parameter mapping.
         *
         * @param array $parameters
         * @return void
         * @static
         */
        public static function resourceParameters($parameters = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->resourceParameters($parameters);
        }
        /**
         * Get or set the verbs used in the resource URIs.
         *
         * @param array $verbs
         * @return array|null
         * @static
         */
        public static function resourceVerbs($verbs = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->resourceVerbs($verbs);
        }
        /**
         * Get the underlying route collection.
         *
         * @return \Illuminate\Routing\RouteCollectionInterface
         * @static
         */
        public static function getRoutes()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getRoutes();
        }
        /**
         * Set the route collection instance.
         *
         * @param \Illuminate\Routing\RouteCollection $routes
         * @return void
         * @static
         */
        public static function setRoutes($routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->setRoutes($routes);
        }
        /**
         * Set the compiled route collection instance.
         *
         * @param array $routes
         * @return void
         * @static
         */
        public static function setCompiledRoutes($routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->setCompiledRoutes($routes);
        }
        /**
         * Remove any duplicate middleware from the given array.
         *
         * @param array $middleware
         * @return array
         * @static
         */
        public static function uniqueMiddleware($middleware)
        {
            return \Illuminate\Routing\Router::uniqueMiddleware($middleware);
        }
        /**
         * Set the container instance used by the router.
         *
         * @param \Illuminate\Container\Container $container
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function setContainer($container)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->setContainer($container);
        }
        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Illuminate\Routing\Router::macro($name, $macro);
        }
        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Illuminate\Routing\Router::mixin($mixin, $replace);
        }
        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Illuminate\Routing\Router::hasMacro($name);
        }
        /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed
         * @throws \BadMethodCallException
         * @static
         */
        public static function macroCall($method, $parameters)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->macroCall($method, $parameters);
        }

    }
}
