<?php
	declare(strict_types=1);

	namespace Fawno\FaceRelations;

	use ErrorException;

	class apiRelations {
		public const ENDPOINT = 'https://face.gob.es/api/v2/relaciones';
		public const PARAMS = [
			'cif' => null,
			'oc' => null,
			'og' => null,
			'ut' => null,
		];

		/**
		 * Check if NIF is valid
		 * @param string $nif
		 * @return bool
		 */
		public static function nif_validation (string $nif) : bool {
			if (!preg_match('~(ES)?([\w\d]{9})~', strtoupper($nif), $parts)) {
				return false;
			}

			$nif = end($parts);

			if (preg_match('~(^[XYZ\d]\d{7})([TRWAGMYFPDXBNJZSQVHLCKE]$)~', $nif, $parts)) {
				$control = 'TRWAGMYFPDXBNJZSQVHLCKE';
				$nie = ['X', 'Y', 'Z'];
				$parts[1] = str_replace(array_values($nie), array_keys($nie), $parts[1]);
				$cheksum = substr($control, $parts[1] % 23, 1);
				return ($parts[2] == $cheksum);
			}

			if (preg_match('~(^[ABCDEFGHIJKLMUV])(\d{7})(\d$)~', $nif, $parts)) {
				$checksum = 0;
				foreach (str_split($parts[2]) as $pos => $val) {
					$checksum += array_sum(str_split((string) ($val * (2 - ($pos % 2)))));
				}
				$checksum = ((10 - ($checksum % 10)) % 10);
				return ($parts[3] == $checksum);
			}

			if (preg_match('~(^[KLMNPQRSW])(\d{7})([JABCDEFGHI]$)~', $nif, $parts)) {
				$control = 'JABCDEFGHI';
				$checksum = 0;
				foreach (str_split($parts[2]) as $pos => $val) {
					$checksum += array_sum(str_split((string) ($val * (2 - ($pos % 2)))));
				}
				$checksum = substr($control, ((10 - ($checksum % 10)) % 10), 1);
				return ($parts[3] == $checksum);
			}

			return false;
		}

    private static function filter_data (array $data) : array {
			return array_filter(filter_var_array($data, [
				'cif' => [
					'filter' => FILTER_CALLBACK,
					'options' => function($value) {
						$value = strtoupper(trim((string) $value));
						$value = preg_replace('~^ES([\w\d]{9})$~', '$1', $value);
						return self::nif_validation($value) ? $value : null;
					},
				],
				'oc' => [
					'filter' => FILTER_CALLBACK,
					'options' => function($value) {
						$value = strtoupper(trim((string) $value));
						return preg_match('~^[\w\d]{9}$~', $value) ? $value : null;
					},
				],
				'og' => [
					'filter' => FILTER_CALLBACK,
					'options' => function($value) {
						$value = strtoupper(trim((string) $value));
						return preg_match('~^[\w\d]{9}$~', $value) ? $value : null;
					},
				],
				'ut' => [
					'filter' => FILTER_CALLBACK,
					'options' => function($value) {
						$value = strtoupper(trim((string) $value));
						return preg_match('~^[\w\d]{9}$~', $value) ? $value : null;
					},
				],
			], false), 'is_string');
		}

		/**
		 * Query relations
     * @param array $data query data, valid keys: 'cif', 'oc', 'og', 'ut'
		 * @return null|array
		 * @throws ErrorException
		 */
		public static function query (array $data) : ?array {
			$data = self::filter_data($data);

			if (empty($data)) {
				return null;
			}

			$url = self::ENDPOINT . '?' . http_build_query($data);

			do {
				sleep(1);

				error_clear_last();
				$response = @file_get_contents($url);
				$error = error_get_last();
				error_clear_last();

				if ($error) {
					throw new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
				}

				if (!$response) {
					sleep(2);
				}
			} while (!$response);

			$items = @json_decode($response, true);

			if (is_array($items) and !empty($items['items'])) {
				return $items;
			}

			return null;
		}
	}
