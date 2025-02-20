<?php

namespace Islandora\Milliner\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Islandora\Chullo\IFedoraApi;
use Islandora\Crayfish\Commons\Client\GeminiClient;
use Islandora\Milliner\Service\MillinerService;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

/**
 * Class MillinerServiceTest
 * @package Islandora\Milliner\Tests
 * @coversDefaultClass \Islandora\Milliner\Service\MillinerService
 */
class SaveMediaTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $modifiedDatePredicate;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger('milliner');
        $this->logger->pushHandler(new NullHandler());

        $this->modifiedDatePredicate = "http://schema.org/dateModified";
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WithNoFileField()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaNoFileField.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class)->reveal();

        $gemini = $this->prophesize(GeminiClient::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WithEmptyFileField()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/MediaEmptyFileField.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class)->reveal();

        $gemini = $this->prophesize(GeminiClient::class)->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows404WhenFileIsNotInGemini()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class)->reveal();

        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn([]);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraHeadError()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $head_response = new Response(404);
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows500WhenNoDescribedbyHeader()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $head_response = new Response(200);
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 500);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraGetError()
    {
        $drupal_response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get(Argument::any(), Argument::any())
            ->willReturn($drupal_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= ';rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' =>  $link]
        );
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            404
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 404);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrows412OnStaleData()
    {
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"',
            ],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal_jsonld_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/StaleMedia.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $drupal->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 412);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaThrowsFedoraPutError()
    {
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"',
            ],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal_jsonld_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $drupal->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora_put_response = new Response(
            403
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_put_response);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $this->expectException(\RuntimeException::class, null, 403);

        $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaReturnsFedoraSuccess()
    {
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"',
            ],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal_jsonld_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $drupal->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);
        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS.jsonld')
        );
        $fedora_put_response = new Response(
            204
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_put_response);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }

    /**
     * @covers ::__construct
     * @covers ::saveMedia
     * @covers ::getFirstPredicate
     * @covers ::getModifiedTimestamp
     * @covers ::processJsonld
     * @covers ::getLinkHeader
     */
    public function testSaveMediaReturnsNoModifiedDate()
    {
        $drupal_json_response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                "Link" => '<http://localhost:8000/media/6?_format=jsonld>; rel="alternate"; type="application/ld+json"',
            ],
            file_get_contents(__DIR__ . '/../../../../static/Media.json')
        );
        $drupal_jsonld_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json'],
            file_get_contents(__DIR__ . '/../../../../static/Media.jsonld')
        );
        $drupal = $this->prophesize(Client::class);
        $drupal->get('http://localhost:8000/media/6?_format=json', Argument::any())
            ->willReturn($drupal_json_response);
        $drupal->get('http://localhost:8000/media/6?_format=jsonld', Argument::any())
            ->willReturn($drupal_jsonld_response);

        $link = '<http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b/fcr:metadata>';
        $link .= '; rel="describedby"';
        $head_response = new Response(
            200,
            ['Link' => $link]
        );
        $drupal->head(Argument::any(), Argument::any())
            ->willReturn($head_response);

        $drupal = $drupal->reveal();

        $fedora_get_response = new Response(
            200,
            ['Content-Type' => 'application/ld+json', 'ETag' => 'W\abc123'],
            file_get_contents(__DIR__ . '/../../../../static/MediaLDP-RS-no_date.jsonld')
        );
        $fedora_put_response = new Response(
            204
        );
        $fedora = $this->prophesize(IFedoraApi::class);
        $fedora->getResource(Argument::any(), Argument::any())
            ->willReturn($fedora_get_response);
        $fedora->saveResource(Argument::any(), Argument::any(), Argument::any())
            ->willReturn($fedora_put_response);
        $fedora = $fedora->reveal();

        $mapping = [
            'drupal' => 'http://localhost:8000/sites/default/files/2017-07/sample_0.jpeg',
            'fedora' => 'http://localhost:8080/fcrepo/rest/ff/b1/5b/4f/ffb15b4f-54db-44ce-ad0b-3588889a3c9b',
        ];
        $gemini = $this->prophesize(GeminiClient::class);
        $gemini->getUrls(Argument::any(), Argument::any())
            ->willReturn($mapping);
        $gemini = $gemini->reveal();

        $milliner = new MillinerService(
            $fedora,
            $drupal,
            $gemini,
            $this->logger,
            $this->modifiedDatePredicate,
            false
        );

        $response = $milliner->saveMedia(
            "field_image",
            "http://localhost:8000/media/6?_format=json",
            "Bearer islandora"
        );

        $status = $response->getStatusCode();
        $this->assertTrue(
            $status == 204,
            "Milliner must return 204 when Fedora returns 204.  Received: $status"
        );
    }
}
