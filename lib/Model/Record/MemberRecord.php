<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\Record;

class MemberRecord extends Record implements HasFileReferences
{
    use HasFileReferencesTrait;

    const RECORD_TYPE = 'member';
    const ID_DELIMITER = '#';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $memberName;

    /**
     * @var string
     */
    private $containerFqn;

    public function __construct(string $type, string $memberName, string $containerFqn = null)
    {
        $this->type = $type;
        $this->memberName = $memberName;
        $this->containerFqn = $containerFqn;
    }

    public static function fromMemberReference(MemberReference $memberReference): self
    {
        return new self($memberReference->type(), $memberReference->memberName(), $memberReference->containerFqn());
    }

    /**
     * {@inheritDoc}
     */
    public function recordType(): string
    {
        return self::RECORD_TYPE;
    }

    public function identifier(): string
    {
        return $this->type . self::ID_DELIMITER . $this->memberName;
    }

    public static function fromIdentifier(string $identifier): self
    {
        $parts = explode(self::ID_DELIMITER, $identifier);
        if (!isset($parts[1])) {
            $parts[1] = 'unknown';
        }
        [$type, $memberName] = $parts;

        return new self($type, $memberName);
    }

    public function memberName(): string
    {
        return $this->memberName;
    }

    public function containerFqn(): ?string
    {
        return $this->containerFqn;
    }

    public function type(): string
    {
        return $this->type;
    }
}
