<?php

namespace FastApi\CrudScaffold\Console\Commands;

use FastApi\CrudScaffold\Support\Concerns\InteractsWithGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeFastApiCommand extends Command
{
    use InteractsWithGenerator;

    protected $signature = 'make:fastapi
                            {name : Table or model name, e.g. Product}
                            {flags? : Combined flags such as -mcrfrepo}
                            {--m|mode= : Compact flags style, e.g. -mcrfrepo}
                            {--routes : Append apiResource route to routes/api.php}
                            {--no-routes : Do not append routes even if config enables it}';

    protected $description = 'Generate API CRUD scaffold files with one command.';

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $context = $this->buildContext((string) $this->argument('name'));
        $flagsInput = $this->resolveFlagsInput();
        $flags = $this->normalizeFlags($flagsInput);

        $this->info("Generating Fast API scaffold for {$context['model']}...");
        $this->generateModel($context);

        if (in_array('m', $flags, true)) {
            $this->generateMigration($context);
        }

        if (in_array('repo', $flags, true)) {
            $this->generateRepository($context);
        }

        if (in_array('c', $flags, true)) {
            $this->generateController($context);
        }

        if (in_array('r', $flags, true)) {
            $this->generateResource($context);
        }

        if (in_array('f', $flags, true)) {
            $this->generateRequests($context);
        }

        if ($this->shouldAppendRoutes()) {
            $appended = $this->appendApiRouteIfMissing($context);
            $this->line($appended ? ' - Appended apiResource route.' : ' - Route already exists or api.php missing.');
        }

        $this->newLine();
        $this->info('Fast API scaffold generation completed.');

        return self::SUCCESS;
    }

    protected function resolveFlagsInput(): ?string
    {
        $compactOption = $this->option('mode');

        if (is_string($compactOption) && $compactOption !== '') {
            // Supports Artisan compact option format: -mcrfrepo
            return 'm' . $compactOption;
        }

        $argumentFlags = $this->argument('flags');

        return is_string($argumentFlags) ? $argumentFlags : null;
    }

    protected function generateMigration(array $context): void
    {
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$context['table']}_table.php";
        $path = database_path('migrations/' . $fileName);

        $created = $this->writeFileIfMissing(
            $path,
            $this->renderStub('migration.stub', $context)
        );

        $this->line($created ? " - Created migration: {$fileName}" : " - Skipped migration (already exists): {$fileName}");
    }

    protected function generateModel(array $context): void
    {
        $path = app_path("Models/{$context['model']}.php");

        $created = $this->writeFileIfMissing(
            $path,
            $this->renderStub('model.stub', $context)
        );

        $this->line($created ? " - Created model: {$context['model']}" : " - Skipped model: {$context['model']}");
    }

    protected function generateController(array $context): void
    {
        $path = app_path("Http/Controllers/Api/{$context['model']}Controller.php");

        $created = $this->writeFileIfMissing(
            $path,
            $this->renderStub('controller.stub', $context)
        );

        $this->line($created ? " - Created controller: {$context['model']}Controller" : " - Skipped controller: {$context['model']}Controller");
    }

    protected function generateResource(array $context): void
    {
        $path = app_path("Http/Resources/{$context['model']}Resource.php");

        $created = $this->writeFileIfMissing(
            $path,
            $this->renderStub('resource.stub', $context)
        );

        $this->line($created ? " - Created resource: {$context['model']}Resource" : " - Skipped resource: {$context['model']}Resource");
    }

    protected function generateRequests(array $context): void
    {
        $storePath = app_path("Http/Requests/{$context['model']}StoreRequest.php");
        $updatePath = app_path("Http/Requests/{$context['model']}UpdateRequest.php");

        $storeCreated = $this->writeFileIfMissing(
            $storePath,
            $this->renderStub('store-request.stub', $context)
        );

        $updateCreated = $this->writeFileIfMissing(
            $updatePath,
            $this->renderStub('update-request.stub', $context)
        );

        $this->line($storeCreated ? " - Created request: {$context['model']}StoreRequest" : " - Skipped request: {$context['model']}StoreRequest");
        $this->line($updateCreated ? " - Created request: {$context['model']}UpdateRequest" : " - Skipped request: {$context['model']}UpdateRequest");
    }

    protected function generateRepository(array $context): void
    {
        $path = app_path("Repositories/{$context['model']}Repository.php");

        $created = $this->writeFileIfMissing(
            $path,
            $this->renderStub('repository.stub', $context)
        );

        $this->line($created ? " - Created repository: {$context['model']}Repository" : " - Skipped repository: {$context['model']}Repository");
    }

    protected function shouldAppendRoutes(): bool
    {
        if ($this->option('no-routes')) {
            return false;
        }

        if ($this->option('routes')) {
            return true;
        }

        return (bool) config('fastapi.auto_append_routes', false);
    }
}
