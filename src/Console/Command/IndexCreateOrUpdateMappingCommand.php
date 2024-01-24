<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Console\Command;

use Throwable;
use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;

final class IndexCreateOrUpdateMappingCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'laravel-elasticsearch:utils:index-create-or-update-mapping
                            {index-name : The index name}
                            {mapping-file-path : The absolute path where mapping file is located}';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Client $client,
        Filesystem $filesystem
    ) {
        $this->client = $client;
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function handle(): int
    {
        $indexName = $this->argument('index-name');
        $mappingFilePath = $this->argument('mapping-file-path');

        if (! $this->argumentsAreValid(
            $indexName,
            $mappingFilePath
        )) {
            return self::FAILURE;
        }

        if (! $this->client->indices()->exists([
            'index' => $indexName,
        ])) {
            try {
                $this->client->indices()->create([
                    'index' => $indexName,
                    'body' => json_decode($this->filesystem->get($mappingFilePath), true, 512, JSON_THROW_ON_ERROR),
                ]);
            } catch (Throwable $exception) {
                $this->output->writeln(
                    sprintf(
                        '<error>Error creating or updating mapping for index %s, given mapping file: %s - error message: %s.</error>',
                        $indexName,
                        $mappingFilePath,
                        $exception->getMessage()
                    )
                );

                return self::FAILURE;
            }

            $this->output->writeln(
                sprintf(
                    '<info>Index %s doesn\'t exist, a new index was created with mapping/settings using file %s.</info>',
                    $indexName,
                    $mappingFilePath
                )
            );

            return self::SUCCESS;
        }

        try {
            $this->client->indices()->putMapping([
                'index' => $indexName,
                'body' => json_decode($this->filesystem->get($mappingFilePath), true, 512, JSON_THROW_ON_ERROR),
            ]);
        } catch (Throwable $exception) {
            $this->output->writeln(
                sprintf(
                    '<error>Error creating or updating mapping for index %s, given mapping file: %s - error message: %s.</error>',
                    $indexName,
                    $mappingFilePath,
                    $exception->getMessage()
                )
            );

            return self::FAILURE;
        }

        $this->output->writeln(
            sprintf(
                '<info>Mapping created or updated for index %s using file %s.</info>',
                $indexName,
                $mappingFilePath
            )
        );

        return self::SUCCESS;
    }

    private function argumentsAreValid($indexName, $mappingFilePath): bool
    {
        if ($indexName === null ||
            ! is_string($indexName) ||
            $indexName === ''
        ) {
            $this->output->writeln(
                '<error>Argument index-name must be a non empty string.</error>'
            );

            return false;
        }

        if ($mappingFilePath === null ||
            ! is_string($mappingFilePath) ||
            $mappingFilePath === '' ||
            ! $this->filesystem->exists($mappingFilePath)
        ) {
            $this->output->writeln(
                '<error>Argument mapping-file-path must exists on filesystem and must be a non empty string.</error>'
            );

            return false;
        }

        return true;
    }
}
