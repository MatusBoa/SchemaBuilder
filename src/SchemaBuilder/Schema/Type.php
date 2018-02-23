<?php

namespace Davajlama\SchemaBuilder\Schema;

use Davajlama\SchemaBuilder\Schema\Type\BinaryType;
use Davajlama\SchemaBuilder\Schema\Type\CharType;
use Davajlama\SchemaBuilder\Schema\Type\DateTimeType;
use Davajlama\SchemaBuilder\Schema\Type\IntegerType;
use Davajlama\SchemaBuilder\Schema\Type\LongTextType;
use Davajlama\SchemaBuilder\Schema\Type\TextType;
use Davajlama\SchemaBuilder\Schema\Type\TinyIntType;
use Davajlama\SchemaBuilder\Schema\Type\VarcharType;

/**
 * Description of Type
 *
 * @author David Bittner <david.bittner@seznam.cz>
 */
class Type
{
    /**
     * @param int $length
     * @return VarcharType
     */
    public static function varcharType($length)
    {
        return new VarcharType($length);
    }

    /**
     * @param int $length
     * @return CharType
     */
    public static function charType($length)
    {
        return new CharType($length);
    }

    /**
     * @return TextType
     */
    public static function textType()
    {
        return new TextType();
    }

    /**
     * @return LongTextType
     */
    public static function longTextType()
    {
        return new LongTextType();
    }

    /**
     * @param int $length
     * @return BinaryType
     */
    public static function binaryType($length)
    {
        return new BinaryType($length);
    }

    /**
     * @return IntegerType
     */
    public static function integerType()
    {
        return new IntegerType();
    }
    
    /**
     * @return DateTimeType
     */
    public static function dateTimeType()
    {
        return new DateTimeType();
    }

    /**
     * @param int $length
     * @return TinyIntType
     */
    public static function tinyIntType($length = 4)
    {
        return new TinyIntType($length);
    }
    
}
