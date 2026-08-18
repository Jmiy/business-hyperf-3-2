<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Utils\Filesystem;

class Config
{
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var null|Config
     */
    protected $fallback;

    /**
     * Constructor.
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * Get a setting.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed config setting or default when not found
     */
    public function get($key, $default = null)
    {
        if (! array_key_exists($key, $this->settings)) {
            return $this->getDefault($key, $default);
        }

        return $this->settings[$key];
    }

    /**
     * Check if an item exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->settings)) {
            return true;
        }

        return $this->fallback instanceof Config
            ? $this->fallback->has($key)
            : false;
    }

    /**
     * Set a setting.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * Set the fallback.
     *
     * @return $this
     */
    public function setFallback(Config $fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * Try to retrieve a default setting from a config fallback.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed config setting or default when not found
     */
    protected function getDefault($key, $default)
    {
        if (! $this->fallback) {
            return $default;
        }

        return $this->fallback->get($key, $default);
    }
}
