<?php

class DeltraResources
{
	/**
	 * gibt den Wert zu einem übergebenen Key aus
	 * @param string $string
	 * @return string
	 */
	public static function getText($string)
	{
		try {
			return DeltraResources::getTextFromConstant($string);
		} catch (ReflectionException $e) {
			return "Konstante konnte nicht ermittelt werden: Reflection fehlgeschlagen";
		}
	}

    /**
     * sucht in Klasse DeltraConst nach dem per Parameter übergebenen Key und gibt den dazugehörigen Wert formatiert aus.
     * @param string $const
	 * @return string
	 * @throws ReflectionException
     */
    private static function getTextFromConstant($const)
    {
		$constObj = new DeltraConst();
        $className = get_class($constObj);
        if(!defined($className."::".$const))
        {
            return "The key '". $const ."' is not defined";
        }

        $reflection = new ReflectionClass($className);
        $text = $reflection->getConstant($const);
        $text = DeltraResources::formatConst($text);
        return $text;
    }

    public static function isResource($string)
    {
		$text = null;
		try
		{
			$text = DeltraResources::getTextFromConstant($string);
		}
		catch(Exception $e)
		{
			return false;
		}

		return !empty($text);
    }

    private static function formatConst($string)
    {
        $string = strip_tags($string);
        $string = mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");
        $string = html_entity_decode($string, ENT_COMPAT, "UTF-8");
        return $string;
    }

}

?>