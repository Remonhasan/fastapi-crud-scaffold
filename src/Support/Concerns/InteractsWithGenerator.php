<?php

namespace FastApi\CrudScaffold\Support\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait InteractsWithGenerator
{
    protected Filesystem $files;

    protected function normalizeFlags(?string $flags): array
    {
        if (empty($flags)) {
            return ['m', 'c', 'r', 'f', 'repo'];
        }

        $normalized = ltrim(strtolower($flags), '-');
        $tokens = [];

        if (Str::contains($normalized, 'repo')) {
            $tokens[] = 'repo';
            $normalized = str_replace('repo', '', $normalized);
        }

        foreach (str_split($normalized) as $flag) {
            if (in_array($flag, ['m', 'c', 'r', 'f'], true)) {
                $tokens[] = $flag;
            }
        }

        $tokens = array_values(array_unique($tokens));

        // Controller requires repository by design.
        if (in_array('c', $tokens, true) && !in_array('repo', $tokens, true)) {
            $tokens[] = 'repo';
        }

        return $tokens;
    }

    protected function buildContext(string $name): array
    {
        $model = Str::studly(Str::singular($name));
        $modelVariable = Str::camel($model);
        $table = Str::snake(Str::pluralStudly($model));
        $routeUri = Str::kebab(Str::pluralStudly($model));

        return [
            'model' => $model,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => Str::plural($modelVariable),
            'table' => $table,
            'routeUri' => $routeUri,
        ];
    }

    protected function writeFileIfMissing(string $path, string $content): bool
    {
        if ($this->files->exists($path)) {
            return false;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);

        return true;
    }

    protected function renderStub(string $stubFile, array $context): string
    {
        $stubPath = __DIR__ . '/../../../stubs/' . $stubFile;
        $stub = $this->files->get($stubPath);

        foreach ($context as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', (string) $value, $stub);
        }

        return $stub;
    }

    protected function appendApiRouteIfMissing(array $context): bool
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (!$this->files->exists($apiRoutesPath)) {
            return false;
        }

        $controllerImport = 'use App\Http\Controllers\Api\\' . $context['model'] . 'Controller;';
        $routeLine = "Route::apiResource('{$context['routeUri']}', {$context['model']}Controller::class);";
        $content = $this->files->get($apiRoutesPath);

        if (Str::contains($content, $routeLine)) {
            return false;
        }

        if (!Str::contains($content, $controllerImport)) {
            $content = preg_replace(
                '/<\?php\s*/',
                "<?php\n\n" . $controllerImport . "\n",
                $content,
                1
            ) ?? $content;
        }

        $content = rtrim($content) . "\n\n" . $routeLine . "\n";
        $this->files->put($apiRoutesPath, $content);

        return true;
    }
}
