<?php

namespace Illuminate\Tests\Http;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class HttpJsonResponseTest extends TestCase
{
    public function testSetAndRetrieveJsonableData(): void
    {
        $response = new \Illuminate\Http\JsonResponse(new JsonResponseTestJsonableObject);
        $data = $response->getData();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSetAndRetrieveJsonSerializeData(): void
    {
        $response = new \Illuminate\Http\JsonResponse(new JsonResponseTestJsonSerializeObject);
        $data = $response->getData();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSetAndRetrieveArrayableData(): void
    {
        $response = new \Illuminate\Http\JsonResponse(new JsonResponseTestArrayableObject);
        $data = $response->getData();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSetAndRetrieveData(): void
    {
        $response = new \Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $data = $response->getData();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testGetOriginalContent(): void
    {
        $response = new \Illuminate\Http\JsonResponse(new JsonResponseTestArrayableObject);
        $this->assertInstanceOf(JsonResponseTestArrayableObject::class, $response->getOriginalContent());

        $response = new \Illuminate\Http\JsonResponse;
        $response->setData(new JsonResponseTestArrayableObject);
        $this->assertInstanceOf(JsonResponseTestArrayableObject::class, $response->getOriginalContent());
    }

    public function testSetAndRetrieveOptions(): void
    {
        $response = new \Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        $this->assertSame(JSON_PRETTY_PRINT, $response->getEncodingOptions());
    }

    public function testSetAndRetrieveDefaultOptions(): void
    {
        $response = new \Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $this->assertSame(0, $response->getEncodingOptions());
    }

    public function testSetAndRetrieveStatusCode(): void
    {
        $response = new \Illuminate\Http\JsonResponse(['foo' => 'bar'], 404);
        $this->assertSame(404, $response->getStatusCode());

        $response = new \Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @param mixed $data
     *
     * @expectedException \InvalidArgumentException
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testInvalidArgumentExceptionOnJsonError($data): void
    {
        new \Illuminate\Http\JsonResponse(['data' => $data]);
    }

    /**
     * @param mixed $data
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testGracefullyHandledSomeJsonErrorsWithPartialOutputOnError($data): void
    {
        new \Illuminate\Http\JsonResponse(['data' => $data], 200, [], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * @return array
     */
    public function jsonErrorDataProvider()
    {
        // Resources can't be encoded
        $resource = tmpfile();

        // Recursion can't be encoded
        $recursiveObject = new \stdClass();
        $objectB = new \stdClass();
        $recursiveObject->b = $objectB;
        $objectB->a = $recursiveObject;

        // NAN or INF can't be encoded
        $nan = NAN;

        return [
            [$resource],
            [$recursiveObject],
            [$nan],
        ];
    }
}

class JsonResponseTestJsonableObject implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}

class JsonResponseTestJsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}

class JsonResponseTestArrayableObject implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}
