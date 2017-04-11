<?php

namespace Yapapaya\DevOps\WPD {

	class Map {

		public function init( $config, $payload ) {
			
			if(!$this->is_branch( $config, $payload ) || !$this->is_right_branch( $config, $payload )){
				error('200 OK', 'No deploy configured for this ref');
			}
			
		}
		
		public function is_branch($config, $payload){
			$ref_type_param = $this->get_nested_array_value( $payload, $config->schema[ 'ref']['param' ] );
			
			$matches = array();

			$pattern = $config->schema[ 'ref']['pattern' ];
			preg_match( $pattern, $ref_type_param, $matches );

			if ( !isset( $matches[ 0 ] ) || empty( $matches[ 0 ] ) ) {
				return false;
			}

			return true;
		}
		
		public function is_right_branch($config, $payload){
			$ref_name_param = $this->get_nested_array_value( $payload, $config->schema[ 'branch_name']['param' ] );

			$matches = array();

			$pattern = $config->schema[ 'branch_name']['pattern' ];

			preg_match( $pattern, $ref_name_param, $matches );

			if ( !isset( $matches[ 1 ] ) || empty( $matches[ 1 ] ) ) {
				return false;
			}
			
			if($matches[1]!== $config->repo['branch']){
				return false;
			}
			
			return true;
		}

		/**
		 * 
		 * @param type $array
		 * @param type $pathParts
		 * @since 0.1
		 * @return type
		 */
		public function get_nested_array_value( $array, $pathParts ) {

			$current = $array;
			foreach ( $pathParts as $key ) {
				$current = &$current[ $key ];
			}

			return $current;
		}

	}

}