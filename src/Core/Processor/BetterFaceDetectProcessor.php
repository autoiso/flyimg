<?php


namespace Core\Processor;

use Core\Entity\Command;
use Core\Entity\Image\OutputImage;
use CV\Scalar;
use function CV\{imread, cvtColor};


class BetterFaceDetectProcessor extends Processor
{
    /**
     * @var \CV\Dnn\Net
     */
    private $network;

    /**
     * @var float|mixed
     */
    private $sensitivity;
    /**
     * @var int|mixed
     */
    private $maxSize;

    public function __construct($sensitivity = 0.3, $maxSize = 800)
    {
        $this->sensitivity = $sensitivity;
        $this->maxSize = $maxSize;
        $this->network = \CV\DNN\readNetFromCaffe(
            ROOT_DIR . '/models/ssd/res10_300x300_ssd_deploy.prototxt',
            ROOT_DIR . '/models/ssd/res10_300x300_ssd_iter_140000.caffemodel'
        );
;    }


    /**
     * Blurring Faces
     *
     * @param OutputImage $outputImage
     *
     * @throws \Exception
     */
    public function blurFaces(OutputImage $outputImage)
    {
        $this->detect($outputImage);
    }

    private function detect(OutputImage $outputImage)
    {
        $src = imread($outputImage->getInputImage()->sourceImagePath());

        $size = $src->size(); // 2000x500

        $maxSize = max($size->width, $size->height);

        if ($maxSize>$this->maxSize) {
            $divider = $maxSize/$this->maxSize;
            \CV\resize($src, $src, new \CV\Size($size->width / $divider, $size->height / $divider));
        }


        $blob = \CV\DNN\blobFromImage($src, 1, new \CV\Size(), new Scalar(104, 177, 123), true, false);

        $this->network->setInput($blob);
        $r = $this->network->forward();

        for ($i = 0; $i < $r->shape[2]; $i++) {
            $confidence = $r->atIdx([0, 0, $i, 2]);
            if ($confidence > $this->sensitivity) {
                $startX = round($r->atIdx([0, 0, $i, 3]) * $src->cols);
                $startY = round($r->atIdx([0, 0, $i, 4]) * $src->rows);
                $endX = round($r->atIdx([0, 0, $i, 5]) * $src->cols);
                $endY = round($r->atIdx([0, 0, $i, 6]) * $src->rows);

                $geometryW = abs($startX - $endX);
                $geometryH = abs($startY - $endY);

                $blurCmd = new Command(self::IM_MOGRIFY_COMMAND);
                $blurCmd->addArgument("-gravity", "NorthWest");
                $blurCmd->addArgument("-region", "{$geometryW}x{$geometryH}+{$startX}+{$startY}");
                $blurCmd->addArgument("-scale", "10%");
                $blurCmd->addArgument("-scale", "1000%");
                $blurCmd->addArgument($outputImage->getInputImage()->sourceImagePath());

                $this->execute($blurCmd);

            }
        }

    }
}