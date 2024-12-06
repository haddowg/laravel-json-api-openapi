<?php

namespace haddowg\JsonApiOpenApi\Attributes;

class AttributeHelper
{
    /**
     * @template T
     * @param class-string|object $objectOrClass
     * @param class-string<T> $attribute
     * @return T|null
     */
    public static function getAttribute($objectOrClass, string $attribute, ?string $method = null)
    {
        $attributes = self::reflection($objectOrClass, $method)->getAttributes($attribute);

        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * @template T
     * @param class-string|object $objectOrClass
     * @param class-string<T> $attribute
     * @return T[]|null
     */
    public static function getAttributes(object|string $objectOrClass, string $attribute, ?string $method = null)
    {
        $attributes = self::reflection($objectOrClass, $method)->getAttributes($attribute);

        return !empty($attributes) ? array_map(fn ($attribute) => $attribute->newInstance(), $attributes) : null;
    }

    /**
     * @template T of StringValueAttribute
     * @param object|class-string $objectOrClass
     * @param class-string<T> $attribute
     * @return string|null
     */
    public static function getStringFromValueAttribute(object|string $objectOrClass, string $attribute, ?string $method = null)
    {
        $attribute = static::getAttribute($objectOrClass, $attribute, $method);

        return $attribute ? $attribute->value() : null;
    }

    /**
     * @template T of object
     * @param class-string<T>|T $objectOrClass
     * @return \ReflectionClass<T>|\ReflectionMethod|\ReflectionClassConstant
     */
    private static function reflection(object|string $objectOrClass, ?string $method = null): \ReflectionClass|\ReflectionMethod|\ReflectionClassConstant
    {
        $reflection =
            $objectOrClass instanceof \ReflectionClass
                || $objectOrClass instanceof \ReflectionClassConstant
                || $objectOrClass instanceof \ReflectionMethod ?
                $objectOrClass :
                new \ReflectionClass($objectOrClass);

        if ($method && method_exists($reflection, 'getMethod') && method_exists($objectOrClass, $method)) {
            return $reflection->getMethod($method);
        }

        return $reflection;
    }
}
