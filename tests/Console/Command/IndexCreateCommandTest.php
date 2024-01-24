<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Tests\Console\Command;

use Exception;
use Generator;
use Mockery\MockInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use MailerLite\LaravelElasticsearch\Tests\TestCase;

final class IndexCreateCommandTest extends TestCase
{
    public function testCreateIndexMustSucceed(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->expects('create')
                            ->andReturns([]);
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-create',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(0)
            ->expectsOutput('Index valid_index_name created.');
    }

    public function testCreateIndexMustFail(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->expects('create')
                            ->andThrow(
                                new Exception('index already exists test exception')
                            );
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-create',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(1)
            ->expectsOutput('Error creating index valid_index_name, exception message: index already exists test exception.');
    }

    public function testCreateIndexMustFailBecauseIndexAlreadyExists(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->allows('create')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-create',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(1)
            ->expectsOutput('Index valid_index_name already exists and cannot be created.');
    }

    /**
     * @dataProvider invalidIndexNameDataProvider
     */
    public function testArgumentIndexNameIsInValid($invalidIndexName): void
    {
        $this->artisan(
            'laravel-elasticsearch:utils:index-create',
            ['index-name' => $invalidIndexName]
        )->assertExitCode(1)
            ->expectsOutput('Argument index-name must be a non empty string.');
    }

    public static function invalidIndexNameDataProvider(): Generator
    {
        yield [
            null,
        ];

        yield [
            '',
        ];

        yield [
            true,
        ];

        yield [
            1,
        ];

        yield [
            [],
        ];
    }
}
