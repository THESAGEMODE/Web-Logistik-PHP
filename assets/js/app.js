// Konfirmasi untuk tindakan penting: <form data-confirm="Yakin?">
document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', ev => { if (!confirm(f.dataset.confirm)) ev.preventDefault(); }));
const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
