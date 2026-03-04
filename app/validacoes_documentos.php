<?php

if (!function_exists('somenteDigitosDocumento')) {
    function somenteDigitosDocumento(string $valor): string {
        return preg_replace('/\D/', '', $valor) ?? '';
    }
}

if (!function_exists('validarCpf')) {
    function validarCpf(string $cpf): bool {
        $cpf = somenteDigitosDocumento($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;

            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('validarCnpj')) {
    function validarCnpj(string $cnpj): bool {
        $cnpj = somenteDigitosDocumento($cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesos1[$i];
        }

        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int) $cnpj[12] !== $digito1) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesos2[$i];
        }

        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return (int) $cnpj[13] === $digito2;
    }
}

if (!function_exists('validarCpfCnpj')) {
    function validarCpfCnpj(string $documento): bool {
        $somenteDigitos = somenteDigitosDocumento($documento);

        if ($somenteDigitos === '') {
            return true;
        }

        if (strlen($somenteDigitos) === 11) {
            return validarCpf($somenteDigitos);
        }

        if (strlen($somenteDigitos) === 14) {
            return validarCnpj($somenteDigitos);
        }

        return false;
    }
}

if (!function_exists('validarEmail')) {
    function validarEmail(string $email, bool $permitirVazio = true, int $tamanhoMaximo = 150): bool {
        $email = trim($email);

        if ($email === '') {
            return $permitirVazio;
        }

        if ($tamanhoMaximo > 0 && strlen($email) > $tamanhoMaximo) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
