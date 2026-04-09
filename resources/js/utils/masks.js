/**
 * Utilitários de máscara para campos de formulário
 * Centraliza funções de formatação e validação de máscaras para documentos, telefones e CEPs
 */

/**
 * Remove todos os caracteres não numéricos de uma string
 * @param {string} value - Valor de entrada
 * @returns {string} String contendo apenas dígitos
 */
export function digitsOnly(value = "") {
    return String(value).replace(/\D+/g, "");
}

/**
 * Aplica uma máscara a uma string de dígitos
 * @param {string} digits - String de dígitos
 * @param {string} pattern - Padrão da máscara com # para dígitos
 * @returns {string} String formatada com a máscara
 */
export function applyMask(digits, pattern) {
    let index = 0;
    return pattern
        .replace(/#/g, () => digits[index++] ?? "")
        .replace(/([-/\\.() ])+$/, ""); // Remove caracteres especiais no final se não houver dígitos
}

/**
 * Formata CPF ou CNPJ com limite de dígitos
 * @param {string} value - Valor de entrada
 * @param {string} personType - 'individual' para CPF (11 dígitos) ou 'company' para CNPJ (14 dígitos)
 * @returns {string} Valor formatado
 */
export function formatDocument(value, personType) {
    const digits = digitsOnly(value);
    if (personType === "company") {
        // CNPJ: 14 dígitos
        const limitedDigits = digits.slice(0, 14);
        return applyMask(limitedDigits, "##.###.###/####-##");
    } else {
        // CPF: 11 dígitos
        const limitedDigits = digits.slice(0, 11);
        return applyMask(limitedDigits, "###.###.###-##");
    }
}

/**
 * Formata telefone com limite de dígitos
 * @param {string} value - Valor de entrada
 * @returns {string} Valor formatado
 */
export function formatPhone(value) {
    const digits = digitsOnly(value);
    // Limita a 11 dígitos (para celulares com 9 dígitos + DDD)
    const limitedDigits = digits.slice(0, 11);
    const pattern =
        limitedDigits.length > 10 ? "(##) #####-####" : "(##) ####-####";
    return applyMask(limitedDigits, pattern);
}

/**
 * Formata CEP com limite de 8 dígitos
 * @param {string} value - Valor de entrada
 * @returns {string} Valor formatado
 */
export function formatPostalCode(value) {
    const digits = digitsOnly(value);
    // Limita a 8 dígitos
    const limitedDigits = digits.slice(0, 8);
    return applyMask(limitedDigits, "#####-###");
}
