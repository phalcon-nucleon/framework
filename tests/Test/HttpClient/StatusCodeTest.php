<?php

namespace Test\HttpClient;

use Neutrino\Http\Standards\StatusCode;

class StatusCodeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMessages()
    {
        return [
            // Informational 1xx
            StatusCode::CONTINUES                       => ['Continue', StatusCode::CONTINUES],
            StatusCode::SWITCHING_PROTOCOLS             => ['Switching Protocols', StatusCode::SWITCHING_PROTOCOLS],

            // Success 2xx
            StatusCode::OK                              => ['OK', StatusCode::OK],
            StatusCode::CREATED                         => ['Created', StatusCode::CREATED],
            StatusCode::ACCEPTED                        => ['Accepted', StatusCode::ACCEPTED],
            StatusCode::NON_AUTHORITATIVE_INFORMATION   => ['Non-Authoritative Information', StatusCode::NON_AUTHORITATIVE_INFORMATION],
            StatusCode::NO_CONTENT                      => ['No Content', StatusCode::NO_CONTENT],
            StatusCode::RESET_CONTENT                   => ['Reset Content', StatusCode::RESET_CONTENT],
            StatusCode::PARTIAL_CONTENT                 => ['Partial Content', StatusCode::PARTIAL_CONTENT],
            StatusCode::MULTI_STATUS                    => ['Multi-Status', StatusCode::MULTI_STATUS],
            StatusCode::ALREADY_REPORTED                => ['Already Reported', StatusCode::ALREADY_REPORTED],
            StatusCode::IM_USED                         => ['IM Used', StatusCode::IM_USED],

            // Redirection 3xx
            StatusCode::MULTIPLE_CHOICES                => ['Multiple Choices', StatusCode::MULTIPLE_CHOICES],
            StatusCode::MOVED_PERMANENTLY               => ['Moved Permanently', StatusCode::MOVED_PERMANENTLY],
            StatusCode::FOUND                           => ['Found', StatusCode::FOUND], // 1.1
            StatusCode::SEE_OTHER                       => ['See Other', StatusCode::SEE_OTHER],
            StatusCode::NOT_MODIFIED                    => ['Not Modified', StatusCode::NOT_MODIFIED],
            StatusCode::USE_PROXY                       => ['Use Proxy', StatusCode::USE_PROXY],
            // 306 is deprecated but reserved
            StatusCode::TEMPORARY_REDIRECT              => ['Temporary Redirect', StatusCode::TEMPORARY_REDIRECT],
            StatusCode::PERMANENT_REDIRECT              => ['Permanent Redirect', StatusCode::PERMANENT_REDIRECT],

            // Client Error 4xx
            StatusCode::BAD_REQUEST                     => ['Bad Request', StatusCode::BAD_REQUEST],
            StatusCode::BAD_UNAUTHORIZED                => ['Unauthorized', StatusCode::BAD_UNAUTHORIZED],
            StatusCode::PAYMENT_REQUIRED                => ['Payment Required', StatusCode::PAYMENT_REQUIRED],
            StatusCode::FORBIDDEN                       => ['Forbidden', StatusCode::FORBIDDEN],
            StatusCode::NOT_FOUND                       => ['Not Found', StatusCode::NOT_FOUND],
            StatusCode::METHOD_NOT_ALLOWED              => ['Method Not Allowed', StatusCode::METHOD_NOT_ALLOWED],
            StatusCode::NOT_ACCEPTABLE                  => ['Not Acceptable', StatusCode::NOT_ACCEPTABLE],
            StatusCode::PROXY_AUTHENTICATION_REQUIRED   => ['Proxy Authentication Required', StatusCode::PROXY_AUTHENTICATION_REQUIRED],
            StatusCode::REQUEST_TIMEOUT                 => ['Request Timeout', StatusCode::REQUEST_TIMEOUT],
            StatusCode::CONFLICT                        => ['Conflict', StatusCode::CONFLICT],
            StatusCode::GONE                            => ['Gone', StatusCode::GONE],
            StatusCode::LENGTH_REQUIRED                 => ['Length Required', StatusCode::LENGTH_REQUIRED],
            StatusCode::PRECONDITION_FAILED             => ['Precondition Failed', StatusCode::PRECONDITION_FAILED],
            StatusCode::REQUEST_ENTITY_TOO_LARGE        => ['Request Entity Too Large', StatusCode::REQUEST_ENTITY_TOO_LARGE],
            StatusCode::REQUEST_URI_TOO_LONG            => ['Request-URI Too Long', StatusCode::REQUEST_URI_TOO_LONG],
            StatusCode::UNSUPPORTED_MEDIA_TYPE          => ['Unsupported Media Type', StatusCode::UNSUPPORTED_MEDIA_TYPE],
            StatusCode::REQUEST_RANGE_NOT_SATISFIABLE   => ['Requested Range Not Satisfiable', StatusCode::REQUEST_RANGE_NOT_SATISFIABLE],
            StatusCode::EXPECTATION_FAILED              => ['Expectation Failed', StatusCode::EXPECTATION_FAILED],
            StatusCode::I_AM_A_TEAPOT                   => ['I\'m a teapot', StatusCode::I_AM_A_TEAPOT],
            StatusCode::UNPROCESSABLE_ENTITY            => ['Unprocessable Entity', StatusCode::UNPROCESSABLE_ENTITY],
            StatusCode::LOCKED                          => ['Locked', StatusCode::LOCKED],
            StatusCode::FAILED_DEPENDENCY               => ['Failed Dependency', StatusCode::FAILED_DEPENDENCY],
            StatusCode::UPDATE_REQUIRED                 => ['Upgrade Required', StatusCode::UPDATE_REQUIRED],
            StatusCode::PRECONDITION_REQUIRED           => ['Precondition Required', StatusCode::PRECONDITION_REQUIRED],
            StatusCode::TOO_MANY_REQUESTS               => ['Too Many Requests', StatusCode::TOO_MANY_REQUESTS],
            StatusCode::REQUEST_HEADER_FIELDS_TOO_LARGE => ['Request Header Fields Too Large', StatusCode::REQUEST_HEADER_FIELDS_TOO_LARGE],

            // Server Error 5xx
            StatusCode::INTERNAL_SERVER_ERROR           => ['Internal Server Error', StatusCode::INTERNAL_SERVER_ERROR],
            StatusCode::NOT_IMPLEMENTED                 => ['Not Implemented', StatusCode::NOT_IMPLEMENTED],
            StatusCode::BAD_GATEWAY                     => ['Bad Gateway', StatusCode::BAD_GATEWAY],
            StatusCode::SERVICE_UNAVAILABLE             => ['Service Unavailable', StatusCode::SERVICE_UNAVAILABLE],
            StatusCode::GATEWAY_TIMEOUT                 => ['Gateway Timeout', StatusCode::GATEWAY_TIMEOUT],
            StatusCode::HTTP_VERSION_NOT_SUPPORTED      => ['HTTP Version Not Supported', StatusCode::HTTP_VERSION_NOT_SUPPORTED],
            StatusCode::VARIANT_ALSO_NEGOTIATES         => ['Variant Also Negotiates', StatusCode::VARIANT_ALSO_NEGOTIATES],
            StatusCode::INSUFFICIENT_STORAGE            => ['Insufficient Storage', StatusCode::INSUFFICIENT_STORAGE],
            StatusCode::LOOP_DETECTED                   => ['Loop Detected', StatusCode::LOOP_DETECTED],
            StatusCode::BANDWIDTH_LIMIT_EXCEED          => ['Bandwidth Limit Exceeded', StatusCode::BANDWIDTH_LIMIT_EXCEED],
            StatusCode::NOT_EXTENDED                    => ['Not Extended', StatusCode::NOT_EXTENDED],
            StatusCode::NETWORK_AUTHENTICATION_REQUIRED => ['Network Authentication Required', StatusCode::NETWORK_AUTHENTICATION_REQUIRED],
        ];
    }

    /**
     * @dataProvider dataMessages
     */
    public function testMessages($expected, $code)
    {
        $this->assertEquals($expected, StatusCode::message($code));
    }
}
