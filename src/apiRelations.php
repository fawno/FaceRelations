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
				[, $digits, $char] = $parts;

				$control = 'TRWAGMYFPDXBNJZSQVHLCKE';
				$nie = ['X', 'Y', 'Z'];

				$digits = (int) str_replace($nie, array_keys($nie), $digits);

				$cheksum = substr($control, $digits % 23, 1);

				return ($char === $cheksum);
			}

			if (preg_match('~^[ABCDEFGHJKLMNPQRSUVW](\d{7})([JABCDEFGHI\d]$)~', $nif, $parts)) {
				[, $digits, $char] = $parts;

				$checksum = 0;
				foreach (str_split($digits) as $pos => $val) {
					$checksum += array_sum(str_split((string) ((int) $val * (2 - ($pos % 2)))));
				}

				$control = 'JABCDEFGHI';
				$checksum1 = (string) ((10 - ($checksum % 10)) % 10);
				$checksum2 = substr($control, (int) $checksum1, 1);

				return ($char === $checksum1 || $char === $checksum2);
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
