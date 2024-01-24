<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Tests\Console\Command;

use Exception;
use Generator;
use Mockery\MockInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use MailerLite\LaravelElasticsearch\Tests\TestCase;

final class IndexDeleteCommandTest extends TestCase
{
    public function testIndexDeleteMustSucceed(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('delete')
                            ->andReturns([]);
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-delete',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(0)
            ->expectsOutput('Index valid_index_name deleted.');
    }

    public function testIndexDeleteMustFail(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('delete')
                            ->andThrow(
                                new Exception('error creating index test exception')
                            );
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-delete',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(1)
            ->expectsOutput('Error deleting index valid_index_name, exception message: error creating index test exception.');
    }

    public function testIndexDeleteMustFailBecauseIndexDoesntExists(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->allows('create')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:index-delete',
            ['index-name' => 'valid_index_name']
        )->assertExitCode(1)
            ->expectsOutput('Index valid_index_name doesn\'t exists and cannot be deleted.');
    }

    /**
     * @dataProvider invalidIndexNameDataProvider
     */
    public function testArgumentIndexNameIsInValid($invalidIndexName): void
    {
        $this->artisan(
            'laravel-elasticsearch:utils:index-delete',
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
