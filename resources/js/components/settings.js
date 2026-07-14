/**
 * Settings Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabPengaturan = document.getElementById('tab-pengaturan');
    if (!tabPengaturan) return;

    function updateModelPlaceholder(provider) {
        const modelInput = document.getElementById('modelName');
        const helpText = document.getElementById('modelHelpText');
        if (!modelInput || !helpText) return;
        const isEn = document.documentElement.lang === 'en';
        
        if (provider === 'groq') {
            modelInput.placeholder = 'llama3-8b-8192';
            if (modelInput.value === 'gemini-2.5-flash' || modelInput.value === 'gemini-1.5-flash') {
                modelInput.value = 'llama3-8b-8192';
            }
            helpText.innerHTML = isEn 
                ? 'Recommended Groq models: <code>llama3-8b-8192</code> or <code>mixtral-8x7b-32768</code>.' 
                : 'Rekomendasi model Groq: <code>llama3-8b-8192</code> atau <code>mixtral-8x7b-32768</code>.';
        } else {
            modelInput.placeholder = 'gemini-2.5-flash';
            if (modelInput.value === 'llama3-8b-8192' || modelInput.value === 'mixtral-8x7b-32768') {
                modelInput.value = 'gemini-2.5-flash';
            }
            helpText.innerHTML = isEn 
                ? 'Recommended Gemini models: <code>gemini-2.5-flash</code> or <code>gemini-1.5-flash</code>.' 
                : 'Rekomendasi model Gemini: <code>gemini-2.5-flash</code> atau <code>gemini-1.5-flash</code>.';
        }
    }

    // Wire Tom Select onChange + run once on load to sync UI with saved setting
    const el = document.getElementById('aiProvider');
    if (el) {
        updateModelPlaceholder(el.value);               // initial sync
        el.addEventListener('change', () => {
            updateModelPlaceholder(el.value);
        });
    }
});
