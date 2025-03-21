<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Console\Command;

use Throwable;
use Illuminate\Console\Command;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;

final class IndexCreateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'laravel-elasticsearch:utils:index-create
                            {index-name : The index name}';

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        Client $client
    ) {
        $this->client = $client;

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

        if (! $this->argumentIsValid($indexName)) {
            return self::FAILURE;
        }

        if ($this->client->indices()->exists([
            'index' => $indexName,
        ])) {
            $this->output->writeln(
                sprintf(
                    '<error>Index %s already exists and cannot be created.</error>',
                    $indexName
                )
            );

            return self::FAILURE;
        }

        try {
            $this->client->indices()->create([
                'index' => $indexName,
            ]);
        } catch (Throwable $exception) {
            $this->output->writeln(
                sprintf(
                    '<error>Error creating index %s, exception message: %s.</error>',
                    $indexName,
                    $exception->getMessage()
                )
            );

            return self::FAILURE;
        }

        $this->output->writeln(
            sprintf(
                '<info>Index %s created.</info>',
                $indexName
            )
        );

        return self::SUCCESS;
    }

    private function argumentIsValid($indexName): bool
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

        return true;
    }
}
