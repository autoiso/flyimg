<?php


namespace Core\Processor;


use Core\DB\TextDetectStorage;
use Core\Entity\Command;
use Core\Entity\Image\OutputImage;
use Google\Cloud\Vision\V1\EntityAnnotation;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Vertex;
use Google\Protobuf\Internal\RepeatedField;

class TextDetectProcessor extends Processor
{
    /**
     * @var array
     */
    private $matchPatterns = [];

    /**
     * @var TextDetectStorage
     */
    private $detectStorage;

    private $nocacheParam;
    private $nocacheParamValue;

    private $areasToBlur;
    private $blurFactor;

    public function __construct(array $settings, array $matchPatterns)
    {
        $this->matchPatterns = $matchPatterns;
        $this->detectStorage = new TextDetectStorage($settings['pg_table_name']);
        $this->nocacheParam = $settings['nocache_param'];
        $this->nocacheParamValue = $settings['nocache_param_value'];
        $this->blurFactor = $settings['blur_factor'];
    }

    public function blurTexts(OutputImage $outputImage)
    {
        $this->areasToBlur = [];
        foreach ($this->getDetectedAreas($outputImage) as $area) {
            $this->processArea($area);
        }
        $this->blurAreas($outputImage);
    }

    private function getDetectedAreas(OutputImage $outputImage): array
    {
        $imgHash = $this->getImageHash($outputImage);
        if ((!isset($_GET[$this->nocacheParam]) || $_GET[$this->nocacheParam] != $this->nocacheParamValue)
            && $areas = $this->detectStorage->getDetectedData($imgHash)) {
            return $areas;
        }
        $areas = $this->processGoogleVisionDetect($outputImage);
        $this->detectStorage->setDetectedData($imgHash, $areas, $outputImage->getInputImage()->sourceImageUrl());
        return $areas;
    }

    private function getImageHash(OutputImage $outputImage): string
    {
        return hash_file('sha512', $outputImage->getInputImage()->sourceImagePath());
    }

    private function processArea(array $area): void
    {
        foreach ($this->matchPatterns as $regexp) {
            if (preg_match($regexp, $area['text'])) {
                $this->areasToBlur[] = $area;
            }
        }
    }

    private function blurAreas(OutputImage $outputImage): void
    {
        if (count($this->areasToBlur)==0) {
            return;
        }
        $mask = '\( -clone 0 -fill white -colorize 100 -fill black ';
        foreach ($this->areasToBlur as $area) {
            $polygon = [];
            foreach ($area['points'] as $point) {
                $polygon[] = sprintf('%s,%s', $point['x'], $point['y']);
            }
            $mask.= sprintf('-draw "polygon %s" ',join(' ', $polygon));
        }
        $mask.= '-alpha off -write mpr:mask +delete \) ';

        $blurCmd = new Command(self::IM_CONVERT_COMMAND);
        $blurCmd->addArgument($outputImage->getInputImage()->sourceImagePath());
        $blurCmd->addArgument($mask);
        $blurCmd->addArgument('-mask','mpr:mask');
        $blurCmd->addArgument('-blur',$this->blurFactor);
        $blurCmd->addArgument("+mask", $outputImage->getInputImage()->sourceImagePath());

//        dump((string) $blurCmd);die;
        $this->execute($blurCmd);
    }

    private function processGoogleVisionDetect(OutputImage $outputImage): array
    {
        $imageAnnotatorClient = new ImageAnnotatorClient();
        $result = [];
        try {
            $imageResource = fopen($outputImage->getInputImage()->sourceImagePath(), 'r');
            $features = [Type::TEXT_DETECTION];
            $response = $imageAnnotatorClient->annotateImage($imageResource, $features);

//            dump($response);die;
            /** @var EntityAnnotation $annotation */
            foreach($response->getTextAnnotations() as $annotation) {
                $item = [
                    'text' => $annotation->getDescription(),
                    'points' => [],
                ];
                /** @var Vertex $vertex */
                foreach ($annotation->getBoundingPoly()->getVertices() as $vertex) {
                    $item['points'][] = ['x' => $vertex->getX(), 'y' => $vertex->getY()];
                }
                $result[] = $item;
            }
        } finally {
            $imageAnnotatorClient->close();
            @fclose($imageResource);
        }
        return $result;
    }
}