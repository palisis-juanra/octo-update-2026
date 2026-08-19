<?php

namespace App\Models;

class Media extends BaseModel
{
    public const VALID_IMAGE_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    public const VALID_VIDEO_TYPES = [
        'vimeo' => 'external/vimeo',
        'youtube' => 'external/youtube',
    ];

    public const REL_LOGO = 'LOGO';

    public const REL_COVER = 'COVER';

    public const REL_GALLERY = 'GALLERY';

    protected ?string $src;

    protected ?string $type;

    protected ?string $rel;

    protected ?string $title;

    protected ?string $caption;

    protected ?string $copyright;

    public function __construct(?string $src, ?string $type = self::VALID_IMAGE_TYPES['jpeg'], ?string $rel = self::REL_LOGO, ?string $title = null, ?string $caption = null, ?string $copyright = null)
    {
        $this->src = $src;
        $this->type = $type;
        $this->rel = $rel;
        $this->title = $title;
        $this->caption = $caption;
        $this->copyright = $copyright;
    }

    public function getSrc(): string
    {
        return $this->src;
    }

    public function setSrc($src): static
    {
        $this->src = $src;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function setRel($rel): static
    {
        $this->rel = $rel;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption($caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright($copyright): static
    {
        $this->copyright = $copyright;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'src' => $this->src,
            'type' => $this->type,
            'rel' => $this->rel,
            'title' => $this->title,
            'caption' => $this->caption,
            'copyright' => $this->copyright,
        ];
    }

    public static function getFileType(string $src): ?string
    {
        $fileType = self::VALID_IMAGE_TYPES['jpeg'];

        $extension = strtolower(pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (isset(self::VALID_IMAGE_TYPES[$extension])) {
            $fileType = self::VALID_IMAGE_TYPES[$extension];
        }
        if (empty($src)) {
            $fileType = null;
        }

        return $fileType;
    }
}
