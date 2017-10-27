<?php

namespace Tutorigo\LaravelMacroHelper\Console;

use Illuminate\Console\Command;

class MacrosCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'ide-helper:macros';

    /** @var string The console command description */
    protected $description = 'Generate an IDE helper file for Laravel macros';

    /** @var array Laravel classes with Macroable support */
    protected $classes = [
        '\Illuminate\Database\Schema\Blueprint',
        '\Illuminate\Support\Arr',
        '\Illuminate\Support\Carbon',
        '\Illuminate\Support\Collection',
        '\Illuminate\Console\Scheduling\Event',
        '\Illuminate\Database\Eloquent\FactoryBuilder',
        '\Illuminate\Filesystem\Filesystem',
        '\Illuminate\Mail\Mailer',
        '\Illuminate\Foundation\Console\PresetCommand',
        '\Illuminate\Routing\Redirector',
        '\Illuminate\Database\Eloquent\Relations\Relation',
        '\Illuminate\Cache\Repository',
        '\Illuminate\Routing\ResponseFactory',
        '\Illuminate\Routing\Route',
        '\Illuminate\Routing\Router',
        '\Illuminate\Validation\Rule',
        '\Illuminate\Support\Str',
        '\Illuminate\Foundation\Testing\TestResponse',
        '\Illuminate\Translation\Translator',
        '\Illuminate\Routing\UrlGenerator',
        '\Illuminate\Database\Query\Builder',
        '\Illuminate\Http\JsonResponse',
        '\Illuminate\Http\RedirectResponse',
        '\Illuminate\Auth\RequestGuard',
        '\Illuminate\Http\Response',
        '\Illuminate\Auth\SessionGuard',
        '\Illuminate\Http\UploadedFile',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $classes = array_merge($this->classes, config('ide-macros.classes', []));

        $fileName = config('ide-macros.filename');
        $file = fopen(base_path($fileName), 'w');
        fwrite($file, "<?php" . PHP_EOL);

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if (!$reflection->hasProperty('macros')) {
                continue;
            }

            $property = $reflection->getProperty('macros');
            $property->setAccessible(true);
            $macros = $property->getValue();

            if (!$macros) {
                continue;
            }

            fwrite($file, "namespace " . $reflection->getNamespaceName() . " {" . PHP_EOL);
            fwrite($file, "    class " . $reflection->getShortName() . " {" . PHP_EOL);

            foreach ($macros as $name => $macro) {
                $reflection = new \ReflectionFunction($macro);
                if ($comment = $reflection->getDocComment()) {
                    fwrite($file, "        $comment" . PHP_EOL);
                }

                fwrite($file, "        public static function " . $name . "(");

                $index = 0;
                foreach ($reflection->getParameters() as $parameter) {
                    if ($index) {
                        fwrite($file, ", ");
                    }

                    fwrite($file, "$" . $parameter->getName());
                    if ($parameter->isOptional()) {
                        fwrite($file, " = " . var_export($parameter->getDefaultValue(), true));
                    }

                    $index++;
                }

                fwrite($file, ") { }" . PHP_EOL);
            }

            fwrite($file, "    }" . PHP_EOL . "}" . PHP_EOL);
        }

        fclose($file);

        $this->line($fileName . ' has been successfully generated.', 'info');
    }
}
