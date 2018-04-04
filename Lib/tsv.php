<?php

class AdTSV {

		private static $instance;

		public $cache = array();
		public static function getInstance() {

				if (!isset(self::$instance)) {

						self::$instance = new TSV();
				}

				return self::$instance;
		}

		public function getTSV($name) {

			if (isset($this->cache[$name])) {

					return $this->cache[$name];
			}

			$tsv = tsv("{$name}.tsv");
			$this->cache[$name] = $tsv;
			return $tsv;
		}
}

?>
