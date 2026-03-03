<?php

namespace PDPhilip\DataSet\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use OmniTerm\HasOmniTerm;

class MakeDataSetCommand extends Command
{
    use HasOmniTerm;

    protected $signature = 'data-set:make {name} {--force}';

    protected $description = 'Create a new DataSet and DataModel pair';

    public function handle(Filesystem $files): int
    {
        $name = Str::singular(Str::studly($this->argument('name')));
        $plural = Str::plural($name);
        $basePath = app_path('DataSets');
        $modelsPath = $basePath.'/Models';
        $setFile = $basePath.'/'.$name.'Set.php';
        $modelFile = $modelsPath.'/'.$name.'DataModel.php';

        $this->newLine();

        if (! $this->option('force')) {
            if ($files->exists($setFile)) {
                $this->omni->statusError('Already Exists', "{$name}Set", [
                    'Use --force to overwrite',
                ]);

                return self::FAILURE;
            }

            if ($files->exists($modelFile)) {
                $this->omni->statusError('Already Exists', "{$name}DataModel", [
                    'Use --force to overwrite',
                ]);

                return self::FAILURE;
            }
        }

        $withSeeder = $this->confirm('Generate a seeder trait?');

        if ($withSeeder) {
            $seedersPath = $basePath.'/Seeders';
            $seederFile = $seedersPath.'/Seeder'.$plural.'.php';

            if (! $this->option('force') && $files->exists($seederFile)) {
                $this->omni->statusError('Already Exists', "Seeder{$plural}", [
                    'Use --force to overwrite',
                ]);

                return self::FAILURE;
            }
        }

        $files->ensureDirectoryExists($modelsPath);
        $files->put($modelFile, $this->buildModelStub($name));
        $this->omni->statusSuccess('Created', "app/DataSets/Models/{$name}DataModel.php");

        if ($withSeeder) {
            $files->ensureDirectoryExists($seedersPath);
            $files->put($seederFile, $this->buildSeederStub($name, $plural));
            $this->omni->statusSuccess('Created', "app/DataSets/Seeders/Seeder{$plural}.php");
        }

        $files->ensureDirectoryExists($basePath);
        $files->put($setFile, $this->buildSetStub($name, $plural, $withSeeder));
        $this->omni->statusSuccess('Created', "app/DataSets/{$name}Set.php");

        $this->newLine();

        return self::SUCCESS;
    }

    protected function buildSetStub(string $name, string $plural, bool $withSeeder): string
    {
        $lcPlural = Str::lcfirst($plural);
        $useModel = "use App\\DataSets\\Models\\{$name}DataModel;";
        $useSeeder = $withSeeder ? "\nuse App\\DataSets\\Seeders\\Seeder{$plural};" : '';
        $trait = $withSeeder ? "\n    use Seeder{$plural};\n" : '';
        $dataReturn = $withSeeder ? "\$this->{$lcPlural}" : '[]';

        return <<<PHP
        <?php

        namespace App\DataSets;

        {$useModel}{$useSeeder}
        use PDPhilip\DataSet\DataSet;

        class {$name}Set extends DataSet
        {{$trait}
            protected string \$modelClass = {$name}DataModel::class;

            protected function data(): array
            {
                return {$dataReturn};
            }
        }
        PHP;
    }

    protected function buildModelStub(string $name): string
    {
        return <<<PHP
        <?php

        namespace App\DataSets\Models;

        use PDPhilip\DataSet\DataModel;

        /**
         * Model Fields - IDE support
         * @property string \$id
         */
        class {$name}DataModel extends DataModel {}
        PHP;
    }

    protected function buildSeederStub(string $name, string $plural): string
    {
        $lcPlural = Str::lcfirst($plural);

        return <<<PHP
        <?php

        namespace App\DataSets\Seeders;

        trait Seeder{$plural}
        {
            protected array \${$lcPlural} = [
                //
            ];
        }
        PHP;
    }
}
