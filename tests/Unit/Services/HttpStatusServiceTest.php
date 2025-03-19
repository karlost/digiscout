<?php

namespace Tests\Unit\Services;

use App\Models\Website;
use App\Services\Monitoring\HttpStatusService;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class HttpStatusServiceTest extends TestCase
{
    protected $httpStatusService;
    protected $websiteMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->websiteMock = Mockery::mock(Website::class);
        
        $this->httpStatusService = new HttpStatusService();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_http_status_service_returns_success_for_200_status()
    {
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com');
        
        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('successful')->andReturn(true);
        $responseMock->shouldReceive('status')->andReturn(200);
        $responseMock->shouldReceive('handlerStats')->andReturn(['redirect_url' => null, 'redirect_count' => 0]);
        
        $httpWithoutVerifyingMock = Mockery::mock();
        $httpWithOptionsMock = Mockery::mock();
        
        Http::shouldReceive('withoutVerifying')->andReturn($httpWithoutVerifyingMock);
        $httpWithoutVerifyingMock->shouldReceive('withOptions')->andReturn($httpWithOptionsMock);
        $httpWithOptionsMock->shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andReturn($responseMock);
            
        $result = $this->httpStatusService->check($this->websiteMock);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(200, $result['value']);
        $this->assertStringContainsString('HTTP Status: 200', $result['additional_data']['message']);
    }
    
    public function test_http_status_service_returns_failure_for_404_status()
    {
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com/notfound');
        
        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('successful')->andReturn(false);
        $responseMock->shouldReceive('status')->andReturn(404);
        $responseMock->shouldReceive('handlerStats')->andReturn(['redirect_url' => null, 'redirect_count' => 0]);
        
        $httpWithoutVerifyingMock = Mockery::mock();
        $httpWithOptionsMock = Mockery::mock();
        
        Http::shouldReceive('withoutVerifying')->andReturn($httpWithoutVerifyingMock);
        $httpWithoutVerifyingMock->shouldReceive('withOptions')->andReturn($httpWithOptionsMock);
        $httpWithOptionsMock->shouldReceive('get')
            ->once()
            ->with('https://example.com/notfound')
            ->andReturn($responseMock);
            
        $result = $this->httpStatusService->check($this->websiteMock);
        
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(404, $result['value']);
        $this->assertStringContainsString('HTTP Status: 404', $result['additional_data']['message']);
    }
    
    public function test_http_status_service_returns_failure_for_server_error()
    {
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://example.com/error');
        
        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('successful')->andReturn(false);
        $responseMock->shouldReceive('status')->andReturn(500);
        $responseMock->shouldReceive('handlerStats')->andReturn(['redirect_url' => null, 'redirect_count' => 0]);
        
        $httpWithoutVerifyingMock = Mockery::mock();
        $httpWithOptionsMock = Mockery::mock();
        
        Http::shouldReceive('withoutVerifying')->andReturn($httpWithoutVerifyingMock);
        $httpWithoutVerifyingMock->shouldReceive('withOptions')->andReturn($httpWithOptionsMock);
        $httpWithOptionsMock->shouldReceive('get')
            ->once()
            ->with('https://example.com/error')
            ->andReturn($responseMock);
            
        $result = $this->httpStatusService->check($this->websiteMock);
        
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(500, $result['value']);
        $this->assertStringContainsString('HTTP Status: 500', $result['additional_data']['message']);
    }
    
    public function test_http_status_service_returns_failure_for_exception()
    {
        $this->websiteMock->shouldReceive('getAttribute')->with('url')->andReturn('https://invalid-domain.xyz');
        
        $httpWithoutVerifyingMock = Mockery::mock();
        $httpWithOptionsMock = Mockery::mock();
        
        Http::shouldReceive('withoutVerifying')->andReturn($httpWithoutVerifyingMock);
        $httpWithoutVerifyingMock->shouldReceive('withOptions')->andReturn($httpWithOptionsMock);
        $httpWithOptionsMock->shouldReceive('get')
            ->once()
            ->with('https://invalid-domain.xyz')
            ->andThrow(new \Exception('Could not resolve host'));
            
        $result = $this->httpStatusService->check($this->websiteMock);
        
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(0, $result['value']);
        $this->assertStringContainsString('Could not resolve host', $result['additional_data']['message']);
    }
}