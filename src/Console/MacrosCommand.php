<?php

namespace Tutorigo\LaravelMacroHelper\Console;

use Illuminate\Console\Command;

class MacrosCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'ide-helper:macros {--filename=}';

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
        '\Illuminate\Console\Command',
        '\Illuminate\Console\Scheduling\Event',
        '\Illuminate\Database\Eloquent\FactoryBuilder',
        '\Illuminate\Filesystem\Filesystem',
        '\Illuminate\Mail\Mailer',
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

        $fileName = $this->option('filename') ?: config('ide-macros.filename');
        $this->file = fopen(base_path($fileName), 'w');
        $this->writeLine("<?php");

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $propertyName = 'macros';
            if (!$reflection->hasProperty($propertyName)) {
                $propertyName = 'globalMacros';
                if (!$reflection->hasProperty($propertyName)) {
                    continue;
                }
            }

            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $macros = $property->getValue();

            if (!$macros) {
                continue;
            }

            $this->generateNamespace($reflection->getNamespaceName(), function () use ($macros, $reflection) {
                $this->generateClass($reflection->getShortName(), function () use ($macros) {
                    foreach ($macros as $name => $macro) {
                        if (is_array($macro)) {
                            list($class, $method) = $macro;
                            $function = new \ReflectionMethod(is_object($class) ? get_class($class) : $class, $method);
                        } else if ($macro instanceof \Closure) {
                            $function = new \ReflectionFunction($macro);
                        } else {
                            $function = new \ReflectionMethod(is_object($macro) ? get_class($macro) : $class, '__invoke');
                        }

                        if ($comment = $function->getDocComment()) {
                            $this->writeLine($comment, $this->indent);

                            if (strpos($comment, '@instantiated') !== false) {
                                $this->generateFunction($name, $function->getParameters(), "public", $function->getReturnType());
                                continue;
                            }
                        }

                        $this->generateFunction($name, $function->getParameters(), "public static", $function->getReturnType());
                    }
                });
            });
        }

        fclose($this->file);

        $this->line("$fileName has been successfully generated.", 'info');
    }

    /**
     * @param string $name
     * @param null|Callable $callback
     */
    protected function generateNamespace($name, $callback = null)
    {
        $this->writeLine("\nnamespace " . $name . " {", $this->indent);

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
        $this->writeLine("class " . $name . " {", $this->indent);

        if ($callback) {
            $this->indent++;
            $callback();
            $this->indent--;
        }

        $this->writeLine("}", $this->indent);
    }

    /**
     * @param string $name
     * @param \ReflectionParameter[] $parameters
     * @param string $type
     * @param null|string $returnType
     * @param null|Callable $callback
     * @throws \ReflectionException
     */
    protected function generateFunction($name, $parameters, $type = '', $returnType = null, $callback = null)
    {
        $this->write(($type ? "$type " : '') . "function $name(", $this->indent);

        $index = 0;
        foreach ($parameters as $parameter) {
            if ($index) {
                $this->write(", ");
            }

            if ($parameter->isVariadic()) {
                $this->write('...');
            }

            if ($parameter->hasType()) {
                if (version_compare(PHP_VERSION, '7.1', '<')) {
                    $this->write($parameter->getType() . " ");
                } else {
                    $paramType = $parameter->getType();
                    if ($paramType instanceof \ReflectionNamedType) {
                        $this->write($paramType->getName() . " ");
                    }
                }
            }

            $this->write("$" . $parameter->getName());
            if ($parameter->isOptional() && !$parameter->isVariadic()) {
                $this->write(" = " . var_export($parameter->getDefaultValue(), true));
            }

            $index++;
        }

        $this->write(")");
        if (version_compare(PHP_VERSION, '7.1', '<')) {
            if ($returnType) {
                $this->write(": \\$returnType");
            }
        } elseif ($returnType instanceof \ReflectionNamedType) {
            $this->write(": \\" . $returnType->getName());
        }
        $this->writeLine(" {");

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
