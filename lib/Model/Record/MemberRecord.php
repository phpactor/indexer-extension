<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\Record;

class MemberRecord extends Record
{
    const RECORD_TYPE = 'member';

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $container;
    /**
     * @var string
     */
    private $name;

    public function __construct(string $type, string $container, string $name)
    {
        $this->type = $type;
        $this->container = $container;
        $this->name = $name;
    }

    public static function fromMemberReference(MemberReference $memberReference): self
    {
        return new self($memberReference->type(), $memberReference->containerFqn(), $memberReference->memberName());
    }

    public function recordType(): string
    {
        return self::RECORD_TYPE;
    }

    public function identifier(): string
    {
        return $this->type . $this->name;
    }
}
