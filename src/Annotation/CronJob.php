<?php

namespace Aequasi\Bundle\CronBundle\Annotation;

/**
 * @Annotation()
 * @Target("CLASS")
 */
use Doctrine\Common\Annotations\Annotation;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CronJob extends Annotation
{
    public $value;
}
