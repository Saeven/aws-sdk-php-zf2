<?php

declare(strict_types=1);

namespace AwsModule\View\Helper;

use Aws\CloudFront\CloudFrontClient;
use AwsModule\View\Exception\InvalidDomainNameException;
use Laminas\View\Helper\AbstractHelper;

use function ltrim;
use function rtrim;
use function sprintf;
use function str_replace;

/**
 * View helper that can render a link to a CloudFront object. It can also create signed URLs
 * using a canned policy
 */
class CloudFrontLink extends AbstractHelper
{
    /** @var string The hostname for CloudFront domains */
    protected $hostname = '.cloudfront.net';

    /** @var CloudFrontClient An instance of CloudFrontClient to be used by the helper */
    protected $client;

    /** @var string The default CloudFront domain to use */
    protected $defaultDomain = '';

    /**
     */
    public function __construct(CloudFrontClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set the CloudFront hostname to use if you are using a custom hostname
     *
     * @param string $hostname
     * @return self
     */
    public function setHostname($hostname)
    {
        $this->hostname = '.' . ltrim($hostname, '.');

        return $this;
    }

    /**
     * Get the CloudFront hostname being used
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Set the CloudFront domain to use if none is provided
     *
     * @param string $defaultDomain
     * @return self
     */
    public function setDefaultDomain($defaultDomain)
    {
        $this->defaultDomain = (string) $defaultDomain;

        return $this;
    }

    /**
     * Get the CloudFront domain to use if none is provided
     *
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
    }

    /**
     * Create a link to a CloudFront object
     *
     * @param  string     $object
     * @param  string     $domain
     * @param  string|int $expiration
     * @param  string     $keyPairId
     * @param  string     $privateKey
     * @return string
     * @throws InvalidDomainNameException
     */
    public function __invoke($object, $domain = '', $expiration = '', $keyPairId = '', $privateKey = '')
    {
        if (empty($domain)) {
            $domain = $this->getDefaultDomain();
        }

        // If $domain is still empty, we throw an exception as it makes no sense
        if (empty($domain)) {
            throw new InvalidDomainNameException('An empty CloudFront domain name was given');
        }

        $url = sprintf(
            'https://%s%s/%s',
            str_replace($this->hostname, '', rtrim($domain, '/')),
            $this->hostname,
            ltrim($object, '/')
        );

        if (empty($expiration)) {
            return $url;
        }

        return $this->client->getSignedUrl([
            'url'         => $url,
            'expires'     => $expiration,
            'key_pair_id' => $keyPairId,
            'private_key' => $privateKey,
        ]);
    }
}
