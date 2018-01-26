<?php declare(strict_types=1);

namespace DaveRandom\LibDNS\Encoding;

use function DaveRandom\LibDNS\encode_domain_name;
use function DaveRandom\LibDNS\encode_ipv4address;
use DaveRandom\LibDNS\Records\ResourceData;

final class ResourceDataEncoder
{
    const ENCODERS = [
        ResourceData\A::class => 'encodeA', /** @uses encodeA */
        ResourceData\NS::class => 'encodeNS', /** @uses encodeNS */
        ResourceData\SOA::class => 'encodeSOA', /** @uses encodeSOA */
    ];

    private function encodeA(EncodingContext $ctx, ResourceData\A $data)
    {
        encode_ipv4address($data->getAddress(), $ctx);
    }

    private function encodeNS(EncodingContext $ctx, ResourceData\NS $data)
    {
        encode_domain_name($data->getAuthoritativeServerName(), $ctx);
    }

    private function encodeSOA(EncodingContext $ctx, ResourceData\SOA $data)
    {
        encode_domain_name($data->getMasterServerName(), $ctx);
        encode_domain_name($data->getResponsibleMailAddress(), $ctx);

        $ctx->appendData(\pack(
            'N5',
            $data->getSerial(),
            $data->getRefreshInterval(),
            $data->getRetryInterval(),
            $data->getExpireTimeout(),
            $data->getTtl()
        ));
    }

    public function encode(EncodingContext $ctx, ResourceData $data)
    {
        $class = \get_class($data);

        if (!\array_key_exists($class, self::ENCODERS)) {
            throw new \UnexpectedValueException("Unknown resource data type: {$class}");
        }

        ([$this, self::ENCODERS[$class]])($ctx, $data);
    }
}
