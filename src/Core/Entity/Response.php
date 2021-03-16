<?php

namespace Core\Entity;

use Core\Entity\Image\OutputImage;
use Core\Handler\ImageHandler;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Class Response
 * @package Core\Entity
 */
class Response extends BaseResponse
{
    /** @var ImageHandler */
    protected $imageHandler;
    /** @var string */
    protected $filePathResolver;
    /** @var int */
    protected $maxAge;

    /**
     * Response constructor.
     *
     * @param $filePathResolver
     * @param $imageHandler
     * @param $maxAge
     */
    public function __construct($imageHandler, $filePathResolver, $maxAge)
    {
        $this->filePathResolver = $filePathResolver;
        $this->imageHandler = $imageHandler;
        $this->maxAge = $maxAge;
        parent::__construct();
        $this->addSecurityHeaders();
    }


    /**
     * @param OutputImage $image
     *
     */
    protected function generateHeaders(OutputImage $image)
    {
        $this->headers->set('Content-Type', $this->imageHandler->responseContentType($image));
        $this->headers->set('Content-Disposition', sprintf('inline;filename="%s"', $image->getOutputImageName()));


        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('P1Y'));
        $this->setExpires($expireDate);
        $longCacheTime = 3600 * 24 * ((int)$this->maxAge);

        $this->setMaxAge($longCacheTime);
        $this->setSharedMaxAge($longCacheTime);
        $this->setPublic();

//        if ($image->getInputImage()->optionsBag()->get('refresh')) {
//            $this->headers->set('Cache-Control', 'no-cache, private');
//            $this->setExpires(null)->expire();
//
//            $this->headers->set('im-identify', $this->imageHandler->imageProcessor()->imageIdentityInformation($image));
//            $this->headers->set('im-command', $image->getCommandString());
//        }
//
//        $this->headers->set('Last-Modified', $this->getLastModifiedDate($image));
    }

    /**
     * Get Last modified Date
     */
    protected function getLastModifiedDate(OutputImage $image): string
    {
        $imagePath =  UPLOAD_DIR . $image->getOutputImageName();
        $lastModifiedGmt = filemtime($imagePath);

        return gmdate("D, d M Y H:i:s T", $lastModifiedGmt);
    }

    /**
     * Add Security Headers
     */
    protected function addSecurityHeaders(): void
    {
        $this->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->headers->set('Content-Security-Policy', "script-src 'self'");
        $this->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $this->headers->set('X-XSS-Protection', '1; mode=block');
        $this->headers->set('X-Content-Type-Options', 'nosniff');
        $this->headers->set('Referrer-Policy', 'strict-origin');
    }

    /**
     * @param OutputImage $image
     *
     */
    public function generateImageResponse(OutputImage $image): void
    {
        $this->setContent($image->getOutputImageContent());
        $this->generateHeaders($image);
        $image->removeOutputImage();
    }

    /**
     * @param OutputImage $image
     *
     */
    public function generatePathResponse(OutputImage $image): void
    {
        $imagePath = sprintf($this->filePathResolver, $image->getOutputImageName());
        $this->setContent($imagePath);
        $image->removeOutputImage();
    }
}
