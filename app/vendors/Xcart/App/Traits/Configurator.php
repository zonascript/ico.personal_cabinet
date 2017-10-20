<?php
namespace Xcart\App\Traits;

use ReflectionClass;
use \Xcart\App\Helpers\Creator;

trait Configurator
{
    /**
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * @return string the short name of this class.
     */
    public static function classNameShort()
    {
        $reflect = new ReflectionClass(self::className());
        return $reflect->getShortName();
    }

    /**
     * Constructor.
     * The default implementation does two things:
     *
     * - Initializes the object with the given configuration `$config`.
     * - Call [[init()]].
     *
     * If this method is overridden in a child class, it is recommended that
     *
     * - the last parameter of the constructor is a configuration array, like `$config` here.
     * - call the parent implementation at the end of the constructor.
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($config = [])
    {
        $this->configure($config);
        $this->init();
    }

    protected function configure($config = [])
    {
        if (!empty($config)) {
            Creator::configure($this, $config);
        }
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }
}