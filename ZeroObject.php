<?php
// namespace RedBeanPHP;

class ZeroObject
{
    /**
	 * This is where the real properties of the bean live. They are stored and retrieved
	 * by the magic getter and setter (__get and __set).
	 *
	 * @var array $properties
	 */
	protected $properties = array();

	/**
	 * Here we keep the meta data of a bean.
	 *
	 * @var array
	 */
    protected $__info = array();
    
	public function initializeForDispense( $type, $primaryKeyColumnName = null )
	{
		$this->__info['type']     = $type;
		if($primaryKeyColumnName){
			$this->setPrimaryKey($primaryKeyColumnName ); //set the primary key column name.
		}
		$this->__info['sys.id']   = 'id';
		$this->__info['sys.orig'] = array( 'id' => 0 );
		$this->__info['tainted']  = TRUE;
		$this->__info['changed']  = TRUE;
		$this->__info['changelist'] = array();
		// if ( $beanhelper ) {
		// 	$this->__info['model'] = $this->beanHelper->getModelForBean( $this );
		// }
		// $this->properties['id']   = 0;
    }
    
    public function setPrimaryKey( $primaryKeyColumnName )
	{
		return $this->setMeta("primaryKey",$primaryKeyColumnName);
    }

	/**
	 * Turns a camelcase property name into an underscored property name.
	 *
	 * Examples:
	 *
	 * - oneACLRoute -> one_acl_route
	 * - camelCase -> camel_case
	 *
	 * Also caches the result to improve performance.
	 *
	 * @param string $property property to un-beautify
	 *
	 * @return string
	 */
	public function beau( $property )
	{
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;
		if (
			( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )
			|| ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) )
			|| ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) )
		) {

			$property = preg_replace( '/List$/', '', $property );
			return $property;
		}
		if ( !isset( $beautifulColumns[$property] ) ) {
			$beautifulColumns[$property] = SELF::camelsSnake( $property );
		}
		return $beautifulColumns[$property];
	}

	/**
	 * Globally available service method for RedBeanPHP.
	 * Converts a camel cased string to a snake cased string.
	 *
	 * @param string $camel camelCased string to converty to snake case
	 *
	 * @return string
	 */
	public static function camelsSnake( $camel )
	{
		return strtolower( preg_replace( '/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $camel ) );
	}

	/**
	 * Magic Getter. Gets the value for a specific property in the bean.
	 * If the property does not exist this getter will make sure no error
	 * occurs. This is because RedBean allows you to query (probe) for
	 * properties. If the property can not be found this method will
	 * return NULL instead.
	 *
	 * Usage:
	 *
	 * <code>
	 * $title = $book->title;
	 * $pages = $book->ownPageList;
	 * $tags  = $book->sharedTagList;
	 * </code>
	 *
	 * The example aboves lists several ways to invoke the magic getter.
	 * You can use the magic setter to access properties, own-lists,
	 * exclusive own-lists (xownLists) and shared-lists.
	 *
	 * @param string $property name of the property you wish to obtain the value of
	 *
	 * @return mixed
	 */
	public function &__get( $property )
	{
		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
		}
		
		$exists         = isset( $this->properties[$property] );

		//If not exists and no field link and no list, bail out.
		if ( !$exists) {
			$NULL = NULL;
			return $NULL;
		}

		

		//If exists and no list or exits and list not changed, bail out.
		if ( $exists) {
			return $this->properties[$property];
		}

	}


        
	/**
	 * Magic Setter. Sets the value for a specific property.
	 * This setter acts as a hook for OODB to mark beans as tainted.
	 * The tainted meta property can be retrieved using getMeta("tainted").
	 * The tainted meta property indicates whether a bean has been modified and
	 * can be used in various caching mechanisms.
	 *
	 * @param string $property name of the property you wish to assign a value to
	 * @param  mixed $value    the value you want to assign
	 *
	 * @return void
	 */
	public function __set( $property, $value )
	{
		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
		} 
	
		$exists         = isset( $this->properties[$property] );

		if ( $value === FALSE ) {
			$value = '0';
		} elseif ( $value === TRUE ) {
			$value = '1';
			/* for some reason there is some kind of bug in xdebug so that it doesnt count this line otherwise... */
		} elseif ( $value instanceof \DateTime ) { 
            $value = $value->format( 'Y-m-d H:i:s' ); 
        }
        $this->__info['changed'] = TRUE;
		$this->properties[$property] = $value;
	}


	public function getMeta( $path, $default = NULL )
	{
		return ( isset( $this->__info[$path] ) ) ? $this->__info[$path] : $default;
	}

    
	/**
	 * Stores a value in the specified Meta information property.
	 * The first argument should be the key to store the value under,
	 * the second argument should be the value. It is common to use
	 * a path-like notation for meta data in RedBeanPHP like:
	 * 'my.meta.data', however the dots are purely for readability, the
	 * meta data methods do not store nested structures or hierarchies.
	 *
	 * @param string $path  path / key to store value under
	 * @param mixed  $value value to store in bean (not in database) as meta data
	 *
	 * @return OODBBean
	 */
	public function setMeta( $path, $value )
	{
		$this->__info[$path] = $value;
		if ( $path == 'type' && !empty($this->beanHelper)) {
			$this->__info['model'] = $this->beanHelper->getModelForBean( $this );
		}

		return $this;
    }
    
    /**
	 * Returns properties of bean as an array.
	 * This method returns the raw internal property list of the
	 * bean. Only use this method for optimization purposes. Otherwise
	 * use the export() method to export bean data to arrays.
	 * This method returns an array with the properties array and
	 * the type (string).
	 *
	 * @return array
	 */
	public function getPropertiesAndType()
	{
		return array( $this->properties, $this->__info['type'] );
	}

}
