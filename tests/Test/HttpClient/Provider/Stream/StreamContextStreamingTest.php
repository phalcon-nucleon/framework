<?php

namespace Test\HttpClient\Provider\Stream;

use Neutrino\Debug\Reflexion;
use Neutrino\HttpClient\Provider\StreamContext;
use Phalcon\Events\Event;
use Test\HttpClient\Provider\TraitWithLocalServer;

/**
 * Class CurlStreamTest
 *
 * @package     Test\Provider\Curl
 */
class StreamContextStreamingTest extends \PHPUnit\Framework\TestCase
{
    use TraitWithLocalServer;

    public function testCall()
    {
        $streamCtxStreaming = new StreamContext\Streaming();

        $whatcher = [];

        $streamCtxStreaming
            ->get('http://127.0.0.1:7999/', ['stream' => true])
            ->setBufferSize(2048)
            ->on(StreamContext\Streaming::EVENT_START, function (Event $ev, StreamContext\Streaming $streamCtxStreaming) use (&$whatcher) {
                if (isset($whatcher[StreamContext\Streaming::EVENT_START])) {
                    throw new \Exception('EVENT_START already raised');
                }

                $whatcher[StreamContext\Streaming::EVENT_START] = [
                    'code'    => $streamCtxStreaming->getResponse()->getCode(),
                    'status'  => $streamCtxStreaming->getResponse()->getHeader()->status,
                    'headers' => $streamCtxStreaming->getResponse()->getHeader()->getHeaders(),
                ];

                $whatcher['memory_start'] = memory_get_peak_usage();
            })
            ->on(StreamContext\Streaming::EVENT_PROGRESS,
                function (Event $ev, StreamContext\Streaming $streamCtxStreaming, $content) use (&$whatcher) {
                    if (!isset($whatcher[StreamContext\Streaming::EVENT_PROGRESS])) {
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS] = [
                            'count'  => 1,
                            'length' => strlen($content)
                        ];
                    } else {
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['count']++;
                        $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length'] += strlen($content);
                    }

                    $whatcher['memory_progress'] = memory_get_peak_usage();

                    if ($whatcher['memory_progress'] > $whatcher['memory_start']) {
                        $delta = $whatcher['memory_progress'] - $whatcher['memory_start'];
                        if ($delta / $whatcher['memory_start'] > 0.05) {
                            throw new \Exception("Memory Leak in progress");
                        }
                    }
                })
            ->on(StreamContext\Streaming::EVENT_FINISH, function (Event $ev, StreamContext\Streaming $curlStream) use (&$whatcher) {
                if (isset($whatcher[StreamContext\Streaming::EVENT_FINISH])) {
                    throw new \Exception('EVENT_FINISH already raised');
                }

                $whatcher[StreamContext\Streaming::EVENT_FINISH] = true;
                $whatcher['memory_finish']                       = memory_get_usage();
            })
            ->send();

        $response = $streamCtxStreaming->getResponse();

        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_START, $whatcher);
        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_PROGRESS, $whatcher);
        $this->assertArrayHasKey(StreamContext\Streaming::EVENT_FINISH, $whatcher);

        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['code'], $response->getCode());
        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['status'], $response->getHeader()->status);
        $this->assertEquals($whatcher[StreamContext\Streaming::EVENT_START]['headers'], $response->getHeader()->getHeaders());

        $this->assertGreaterThanOrEqual(1, $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['count']);
        $this->assertGreaterThanOrEqual(1, $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length']);

        $this->assertGreaterThanOrEqual($response->getHeader()->get('Content-Length'),
            $whatcher[StreamContext\Streaming::EVENT_PROGRESS]['length']);

        if ($whatcher['memory_finish'] > $whatcher['memory_start']) {
            $delta = $whatcher['memory_finish'] - $whatcher['memory_start'];
            if ($delta / $whatcher['memory_start'] > 0.05) {
                throw new \Exception("Memory Leak in progress");
            }
        }
    }

    public function testSetBufferSize()
    {
        $streamCtxStreaming = new StreamContext\Streaming();

        $streamCtxStreaming->setBufferSize(2048);

        $bufferSize = Reflexion::get($streamCtxStreaming, 'bufferSize');

        $this->assertEquals(2048, $bufferSize);
    }
}
