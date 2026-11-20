<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Http;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\HttpClient\Message\ResponseMediator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class ResponseMediatorTest extends TestCase
{
    public function testReturnsOriginalResponse(): void
    {
        $response = new Response(200, [], '{}');
        $mediator = new \Hyperplural\WarfaceSdk\HttpClient\Message\ResponseMediator($response);

        self::assertSame($response, $mediator->getResponse());
    }

    /** @return iterable<string, array{string, array<mixed>}> */
    public static function validBodies(): iterable
    {
        yield 'object' => ['{"value":42}', ['value' => 42]];
        yield 'array' => ['[1,2]', [1, 2]];
        yield 'empty array' => ['[]', []];
    }

    /** @param array<mixed> $expected */
    #[DataProvider('validBodies')]
    public function testDecodesObjectsAndArrays(string $body, array $expected): void
    {
        self::assertSame($expected, (new ResponseMediator(new Response(200, [], $body)))->getBodyContentsDecode());
    }

    /** @return iterable<string, array{string}> */
    public static function scalarBodies(): iterable
    {
        yield 'null' => ['null'];
        yield 'boolean' => ['true'];
        yield 'string' => ['"value"'];
        yield 'number' => ['42'];
    }

    #[DataProvider('scalarBodies')]
    public function testRejectsScalarJson(string $body): void
    {
        $this->expectException(ApiResponseErrorException::class);
        (new ResponseMediator(new Response(200, [], $body)))->getBodyContentsDecode();
    }

    /** @return iterable<string, array{string}> */
    public static function malformedBodies(): iterable
    {
        yield 'malformed' => ['{"value":'];
        yield 'empty' => [''];
    }

    #[DataProvider('malformedBodies')]
    public function testPreservesJsonError(string $body): void
    {
        try {
            (new ResponseMediator(new Response(200, [], $body)))->getBodyContentsDecode();
            self::fail('Expected an invalid JSON exception');
        } catch (ApiResponseErrorException $exception) {
            self::assertInstanceOf(JsonException::class, $exception->getPrevious());
            self::assertSame(200, $exception->getCode());
        }
    }

    public function testReadsSeekableStreamFromTheBeginning(): void
    {
        $stream = Utils::streamFor('{"value":42}');
        $stream->seek(4);

        $mediator = new ResponseMediator(new Response(200, [], $stream));
        self::assertSame(['value' => 42], $mediator->getBodyContentsDecode());
    }

    public function testReadsUnseekableStreamAtStart(): void
    {
        $stream = new NoSeekStream(Utils::streamFor('[1,2]'));

        self::assertSame([1, 2], (new ResponseMediator(new Response(200, [], $stream)))->getBodyContentsDecode());
    }

    public function testRejectsPartiallyConsumedUnseekableStreamEvenWhenRemainderIsValidJson(): void
    {
        $source = Utils::streamFor('prefix[1,2]');
        $source->seek(6);
        $stream = new NoSeekStream($source);

        $this->expectException(ApiResponseErrorException::class);
        (new ResponseMediator(new Response(200, [], $stream)))->getBodyContentsDecode();
    }

    public function testRejectsStreamWhenPositionCannotBeDetermined(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);
        $stream->method('tell')->willThrowException(new RuntimeException('Unknown position'));
        $stream->expects(self::never())->method('getContents');

        try {
            (new ResponseMediator(new Response(200, [], $stream)))->getBodyContentsDecode();
            self::fail('Expected a stream error');
        } catch (ApiResponseErrorException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }

    public function testWrapsRewindFailure(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);
        $stream->method('rewind')->willThrowException(new RuntimeException('Cannot rewind'));

        try {
            (new ResponseMediator(new Response(200, [], $stream)))->getBodyContentsDecode();
            self::fail('Expected a stream error');
        } catch (ApiResponseErrorException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }
}
