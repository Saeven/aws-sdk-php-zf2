<?php

declare(strict_types=1);

namespace AwsModule\Tests\View\Helper;

use Aws\CloudFront\CloudFrontClient;
use AwsModule\View\Exception\InvalidDomainNameException;
use AwsModule\View\Helper\CloudFrontLink;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function file_exists;
use function openssl_csr_new;
use function openssl_csr_sign;
use function openssl_get_privatekey;
use function openssl_pkey_export_to_file;
use function openssl_x509_export;
use function sys_get_temp_dir;
use function time;

class CloudFrontLinkTest extends TestCase
{
    /** @var CloudFrontClient */
    protected $cloudFrontClient;

    /** @var CloudFrontLink */
    protected $viewHelper;

    protected function setUp(): void
    {
        $this->cloudFrontClient = new CloudFrontClient([
            'credentials' => [
                'key' => '1234',
                'secret' => '5678',
            ],
            'region' => 'us-east-1',
            'version' => 'latest',
        ]);

        $this->viewHelper = new CloudFrontLink($this->cloudFrontClient);
    }

    public function testGenerateSimpleLink()
    {
        $link = $this->viewHelper->__invoke('my-object', 'my-domain');
        $this->assertEquals('https://my-domain.cloudfront.net/my-object', $link);
    }

    public function testCanUseDefaultDomain()
    {
        $this->viewHelper->setDefaultDomain('my-default-domain');

        $link = $this->viewHelper->__invoke('my-object');
        $this->assertEquals('https://my-default-domain.cloudfront.net/my-object', $link);
    }

    public function testAssertGivenDomainOverrideDefaultDomain()
    {
        $this->viewHelper->setDefaultDomain('my-default-domain');

        $link = $this->viewHelper->__invoke('my-object', 'my-overriden-domain');
        $this->assertEquals('https://my-overriden-domain.cloudfront.net/my-object', $link);
    }

    public function testCanTrimCloudFrontPartInDomain()
    {
        $link = $this->viewHelper->__invoke('my-object', '123abc.cloudfront.net');
        $this->assertEquals('https://123abc.cloudfront.net/my-object', $link);

        $link = $this->viewHelper->__invoke('my-object', '123abc.cloudfront.net/');
        $this->assertEquals('https://123abc.cloudfront.net/my-object', $link);
    }

    public function testCanUseCustomHostname()
    {
        $this->viewHelper->setHostname('example.com');
        $this->assertEquals('.example.com', $this->viewHelper->getHostname());

        $link = $this->viewHelper->__invoke('my-object', '123abc');
        $this->assertEquals('https://123abc.example.com/my-object', $link);
    }

    public function testFailsWhenDomainIsInvalid()
    {
        $this->expectException(InvalidDomainNameException::class);
        $this->viewHelper->setDefaultDomain('');
        $this->viewHelper->__invoke('my-object');
    }

    public function testGenerateSignedLink()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL is required for this test.');
        }

        $pemFile = sys_get_temp_dir() . '/aws-sdk-php-zf2-cloudfront-test.pem';
        if (!file_exists($pemFile)) {
            // Generate a new Certificate Signing Request and public/private keypair
            $csr = openssl_csr_new([], $keypair);

            // Create a self-signed certificate
            $x509 = openssl_csr_sign($csr, null, $keypair, 1);
            openssl_x509_export($x509, $certificate);

            // Create and save a private key
            $privateKey = openssl_get_privatekey($keypair);
            openssl_pkey_export_to_file($privateKey, $pemFile);
        }

        $this->viewHelper->setHostname('example.com');
        $link = $this->viewHelper->__invoke('my-object', '123abc', time() + 600, 'kpid', $pemFile);
        $this->assertMatchesRegularExpression(
            '#^https\:\/\/123abc\.example\.com\/my-object\?Expires\=(.*)\&Signature\=(.*)\&Key-Pair-Id\=kpid$#',
            $link
        );
    }
}
