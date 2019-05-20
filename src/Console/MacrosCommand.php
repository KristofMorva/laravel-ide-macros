<?php

namespace Tutorigo\LaravelMacroHelper\Console;

use Illuminate\Console\Command;

class MacrosCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'ide-helper:macros {--filename=} {--filename-static=}';

    /** @var string The console command description */
    protected $description = 'Generate an IDE helper file for Laravel macros';

    /** @var array Laravel classes with Macroable support */
    protected $classes = [
        '\Illuminate\Database\Schema\Blueprint',
        '\Illuminate\Support\Arr',
        '\Illuminate\Support\Carbon',
        '\Carbon\Carbon',
        '\Carbon\CarbonImmutable',
        '\Carbon\CarbonInterval',
        '\Carbon\CarbonPeriod',
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

    /** @var resource */
    protected $file;

    /** @var int */
    protected $indent = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $classes = array_merge($this->classes, config('ide-macros.classes', []));

        $fileName = $this->option('filename') ?: config('ide-macros.filename') ?: '_ide_macros.php';
        $fileNameStatic = $this->option('filename-static') ?: config('ide-macros.filename_static') ?: preg_replace('/^(.*)(\.[^.]+)?$/U', '$1_static$2', $fileName);
        $files = [
            $fileName => false,
            $fileNameStatic => true,
        ];
        $macroVariableNames = config('ide-macros.variable_names') ?: ['macros', 'globalMacros'];

        foreach ($files as $fileName => $handleStatic) {
            $this->file = fopen(base_path($fileName), 'w');
            $this->writeLine("<?php");

            foreach ($classes as $class) {
                if (!class_exists($class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($class);
                $macros = null;

                foreach ($macroVariableNames as $variableName) {
                    if (!$reflection->hasProperty($variableName)) {
                        continue;
                    }

                    $property = $reflection->getProperty($variableName);
                    $property->setAccessible(true);
                    $macros = $property->getValue();

                    break;
                }

                if (!$macros) {
                    continue;
                }

                $this->generateNamespace($reflection->getNamespaceName(), function () use ($macros, $reflection, $handleStatic) {
                    $this->generateClass($reflection->getShortName(), function () use ($macros, $handleStatic) {
                        foreach ($macros as $name => $macro) {
                            if ($name === '__construct' || $name === '__destruct') {
                                // Ignore mixin constructor/desctructor
                                continue;
                            }

                            if (is_array($macro)) {
                                list($class, $method) = $macro;
                                $function = new \ReflectionMethod(is_object($class) ? get_class($class) : $class, $method);
                            } else {
                                $function = new \ReflectionFunction($macro);
                            }

                            $comment = $function->getDocComment();

                            if ($comment && strpos($comment, '@' . ($handleStatic ? 'instantiated' : 'static')) !== false) {
                                continue;
                            }

                            if ($comment) {
                                $this->writeLine($comment, $this->indent);
                            }

                            $this->generateFunction($name, $function->getParameters(), "public" . ($handleStatic ? " static" : ""));
                        }
                    });
                });
            }

            fclose($this->file);

            $this->line("$fileName has been successfully generated.", 'info');
        }
    }

    /**
     * @param string $name
     * @param null|Callable $callback
     */
    protected function generateNamespace($name, $callback = null)
    {
        $this->writeLine("namespace $name {", $this->indent);

        if ($callback) {
            $this->indent++;
            $callback();
            $this->indent--;
        }

        $this->writeLine("}", $this->indent);
    }

    /**
     * @param string $name
     * @param null|Callable $callback
     */
    protected function generateClass($name, $callback = null)
    {
        $this->writeLine("class $name {", $this->indent);

        if ($callback) {
            $this->indent++;
            $callback();
            $this->indent--;
        }

        $this->writeLine("}", $this->indent);
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param string $type
     * @param null|Callable $callback
     */
    protected function generateFunction($name, $parameters, $type = '', $callback = null)
    {
        $this->write(($type ? "$type " : '') . "function $name(", $this->indent);

        $index = 0;
        foreach ($parameters as $parameter) {
            if ($index) {
                $this->write(", ");
            }

            $this->write("$" . $parameter->getName());
            if ($parameter->isOptional()) {
                $this->write(" = ".var_export($parameter->getDefaultValue(), true));
            }

            $index++;
        }

        $this->writeLine(") {");

        if ($callback) {
            $callback();
        }

        $this->writeLine();
        $this->writeLine("}", $this->indent);
    }

    protected function write($string, $indent = 0)
    {
        fwrite($this->file, str_repeat('    ', $indent) . $string);
    }

    protected function writeLine($line = '', $indent = 0)
    {
        $this->write($line . PHP_EOL, $indent);
    }
}
