/**
 * Utilitários de formatação para valores decimais
 * Centraliza todas as funções de formatação de moeda, quantidade e números
 */

// Constantes para limites de dígitos
export const DIGIT_LIMITS = {
    QUANTITY: 8, // Até 99999.99
    PRICE: 12, // Até trilhões
    PERCENTAGE: 5, // Até 999.99%
};

/**
 * Formata um valor numérico para moeda brasileira
 * @param value - Valor numérico ou string
 * @returns String formatada como moeda (R$ 1.234,56)
 */
export function formatCurrency(value) {
    if (value === null || value === undefined) return "R$ 0,00";

    const numericValue = typeof value === "string" ? parseFloat(value) : value;
    if (isNaN(numericValue)) return "R$ 0,00";

    return numericValue.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
    });
}

/**
 * Formata um valor numérico para quantidade (sempre com 2 casas decimais)
 * @param value - Valor numérico ou string
 * @returns String formatada como quantidade (1.234,56)
 */
export function formatQuantity(value) {
    if (value === null || value === undefined) return "0,00";

    const numericValue = typeof value === "string" ? parseFloat(value) : value;
    if (isNaN(numericValue)) return "0,00";

    return numericValue.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/**
 * Converte uma string de dígitos (centavos) para formato brasileiro
 * @param digits - String contendo apenas dígitos representando centavos
 * @param maxDigits - Número máximo de dígitos permitidos (padrão: 8)
 * @returns String formatada em PT-BR
 */
export function formatFromDigitString(
    digits,
    maxDigits = DIGIT_LIMITS.QUANTITY
) {
    let value = String(digits || "").replace(/\D/g, "");
    if (value === "") return "";

    value = value.slice(0, maxDigits);

    // Garante pelo menos 2 dígitos (centavos)
    while (value.length < 2) {
        value = "0" + value;
    }

    const cents = value.slice(-2);
    const units = value.slice(0, -2) || "0";
    const numericValue = parseFloat(units + "." + cents);

    return numericValue.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

/**
 * Formata entrada de quantidade em tempo real
 * @param event - Evento de input
 * @param currentRawDigits - Dígitos crus atuais (string)
 * @returns Objeto com formatted (string formatada) e rawDigits (string dos dígitos crus)
 */
export function formatQuantityInput(event, currentRawDigits = "") {
    const target = event.target;
    let value = target.value.replace(/\D/g, ""); // Remove tudo exceto dígitos

    if (value === "") {
        return { formatted: "", rawDigits: "" };
    }

    // Garante no máximo 8 dígitos (até 99999.99)
    value = value.slice(0, DIGIT_LIMITS.QUANTITY);

    // Adiciona zeros à esquerda para garantir pelo menos 2 dígitos (centavos)
    while (value.length < 2) {
        value = "0" + value;
    }

    // Separa centavos
    const cents = value.slice(-2);
    const units = value.slice(0, -2) || "0";
    const numericValue = parseFloat(units + "." + cents);

    // Formata para BRL sem moeda
    const formatted = numericValue.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return { formatted, rawDigits: value };
}

/**
 * Formata entrada de preço em tempo real
 * @param event - Evento de input
 * @param rawDigitsRef - Ref para armazenar os dígitos crus
 * @returns String formatada como moeda
 */
export function formatPriceInput(event, rawDigitsRef) {
    const target = event.target;
    let value = target.value.replace(/\D/g, ""); // Remove tudo exceto dígitos

    if (value === "") {
        rawDigitsRef.value = "";
        return { formatted: "", numeric: 0 };
    }

    // Garante no máximo 12 dígitos (até trilhões)
    value = value.slice(0, DIGIT_LIMITS.PRICE);

    // Adiciona zeros à esquerda para garantir pelo menos 3 dígitos
    while (value.length < 3) {
        value = "0" + value;
    }

    // Separa centavos
    const cents = value.slice(-2);
    const reais = value.slice(0, -2);
    const numericValue = parseFloat(reais + "." + cents);

    // Formata para BRL
    const formatted = numericValue.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    rawDigitsRef.value = value;
    return { formatted, numeric: numericValue };
}

/**
 * Inicializa um campo de preço com valor existente
 * @param initialValue - Valor inicial
 * @param rawDigitsRef - Ref para armazenar os dígitos crus
 * @returns String formatada para exibição
 */
export function initializePriceDisplay(initialValue, rawDigitsRef) {
    if (!initialValue) {
        rawDigitsRef.value = "";
        return "";
    }

    const numericValue =
        typeof initialValue === "string"
            ? parseFloat(initialValue)
            : initialValue;

    if (isNaN(numericValue)) {
        rawDigitsRef.value = "";
        return "";
    }

    const formatted = numericValue.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
    });

    rawDigitsRef.value = numericValue.toString();
    return formatted;
}

/**
 * Converte valor formatado de volta para número
 * @param formattedValue - String formatada (ex: "R$ 1.234,56" ou "1.234,56")
 * @returns Número
 */
export function parseFormattedValue(formattedValue) {
    if (!formattedValue) return 0;

    // Remove símbolos de moeda e espaços
    const cleaned = formattedValue
        .replace(/R\$\s?/g, "")
        .replace(/\./g, "")
        .replace(",", ".");

    const numeric = parseFloat(cleaned);
    return isNaN(numeric) ? 0 : numeric;
}

/**
 * Valida se um valor está dentro dos limites permitidos
 * @param value - Valor a validar
 * @param type - Tipo de campo ('quantity', 'price', 'percentage')
 * @returns true se válido
 */
export function isValidDecimalValue(value, type) {
    if (isNaN(value) || !isFinite(value)) return false;

    const maxValue = Math.pow(10, DIGIT_LIMITS[type] - 2) - 0.01; // -0.01 para evitar arredondamento
    return value >= 0 && value <= maxValue;
}
