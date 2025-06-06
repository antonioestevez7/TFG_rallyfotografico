<?php
	
/**
 * FUNCIONES DE VALIDACIÓN
 */


	/**
     * Método que devuelve valor de una clave del REQUEST limpia o cadena vacía si no existe
     * @param {string} - Clave del REQUEST de la que queremos obtener el valor
     * @return {string}
     */
    function obtenerValorCampo(string $campo): string{
      if (isset($_REQUEST[$campo])){
          $valor = trim(htmlspecialchars($_REQUEST[$campo], ENT_QUOTES, "UTF-8"));
      }else{
          $valor = "";
      }
      
      return $valor;
  }


    /**
     * Método que valida si un texto no esta vacío
     * @param {string} - Texto a validar
     * @return {boolean}
     */
    function validar_requerido(string $texto): bool
    {
        return !(trim($texto) == '');
    }

    /**
     * Método que valida si es un número entero 
     * @param {string} - Número a validar
     * @return {bool}
     */
    function validar_entero(string $numero): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_INT) === FALSE) ? False : True;
    }

 /**
     * Método que valida si es un número entero positivo 
     * @param {string} - Número a validar
     * @return {bool}
     */
    function validar_entero_positivo(string $numero): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_INT) === FALSE || $numero <= 0) ? False : True;
    }

/**
     * Método que valida si la longitud de una cadena está entre dos valores pasados 
     * @param {string} - Número a validar
     * @param {int} - Tamaño mínimo
     * @param {int} - Tamaño máximo
     * @return {bool}
     */

    function validarLongitudCadena($cadena, $min ,$max): bool
    {
      if (strlen($cadena)>=$min && strlen($cadena)<=$max) return true;
      else return false;        
    }
/*
    * Método que valida si el texto tiene un formato válido de E-Mail
    * @param {string} - Email
    * @return {bool}
    */
    function validarEmail(string $texto): bool
    {
        return (filter_var($texto, FILTER_VALIDATE_EMAIL) === FALSE) ? False : True;
    }

/*
    * Método que valida si es un número entero y está entre unos límites
    * @param {string} - $numero Número a validar
    * @param {int} - $limiteInferior Límite inferior
    * @param {int} - $limiteSuperior Límite superior
    * @param {string} - Número a validar
    * @return {bool}
    */
    function validarEnteroLimites(string $numero, int $limiteInferior , int $limiteSuperior): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_INT,  ["options" => ["min_range" => $limiteInferior, "max_range" => $limiteSuperior]]) === False) ? False : True;
    }

/*
    * Método que valida si es un número decimal positivo
    * @param {string} - Número a validar
    * @return {bool}
    */
    function validarDecimalPositivo(string $numero): bool
    {
        return (filter_var($numero, FILTER_VALIDATE_FLOAT) === FALSE || $numero <= 0) ? False : True;
    }



/**
 * FIN FUNCIONES DE VALIDACIÓN
 */



    

/**
 * FUNCIONES TRABAJAR CON BBDD
 */
  function conectarPDO(string $host, string $user, string $password, string $bbdd): PDO 
    {
        try 
        {
          $mysql="mysql:host=$host;dbname=$bbdd;charset=utf8";
          $conexion = new PDO($mysql, $user, $password);
          // set the PDO error mode to exception
          $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
        } 
        catch (PDOException $exception) 
        {
           exit($exception->getMessage());
        }
        return $conexion;    
    }
	
  function resultadoConsulta (PDO $conexion, string $consulta): PDOStatement 
    {
		$resultado = $conexion->query($consulta);
		return $resultado;
	}



/**
 * FIN FUNCIONES TRABAJAR CON BBDD
 */

	
?>