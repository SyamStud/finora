# Finora

Finora adalah aplikasi pencatatan keuangan pribadi untuk memantau uang yang tersimpan di sejumlah rekening, pembagian di dalamnya, pemasukan, pengeluaran, dan aset.

## Language

**Rekening**:
Tempat nyata tempat uang disimpan, seperti BCA, Mandiri, GoPay, atau kas. Satu rekening dapat memiliki banyak pocket.
_Avoid_: Account, dompet, wallet

**Pocket**:
Pembagian uang di dalam satu rekening untuk tujuan tertentu, misalnya pocket jajan. Pocket bukan rekening terpisah dan tidak bisa berdiri sendiri.
_Avoid_: Wallet, personal wallet, sub-account

**Saldo Dasar**:
Bagian saldo rekening yang belum dialokasikan ke pocket mana pun.
_Avoid_: Saldo rekening, saldo tersedia

**Saldo Rekening**:
Jumlah saldo dasar rekening dan seluruh pocket di dalamnya.
_Avoid_: Saldo bank, total saldo akun

**Alokasi**:
Pemindahan uang antara saldo dasar dan pocket, atau antar pocket di dalam satu rekening. Alokasi tidak mengubah total saldo rekening dan bukan pemasukan maupun pengeluaran.
_Avoid_: Transfer internal, pindah dana

**Transaksi**:
Pemasukan atau pengeluaran uang. Transaksi dapat dibebankan langsung ke sebuah pocket di dalam rekening.
_Avoid_: Pembayaran, jurnal

**Transfer**:
Pemindahan uang antar dua rekening yang berbeda. Transfer tidak dihitung sebagai pemasukan atau pengeluaran.
_Avoid_: Alokasi, pindah dana

**Aset**:
Nilai yang dimiliki di luar uang tunai di rekening, seperti reksa dana atau emas, yang ikut dihitung dalam kekayaan bersih.
_Avoid_: Investasi, portofolio
