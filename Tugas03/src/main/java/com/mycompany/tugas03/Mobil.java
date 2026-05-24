package com.mycompany.tugas03;

public class Mobil extends Kendaraan {

    int jumlahPintu;
    String bahanBakar;

    public Mobil(String merk, String warna, int tahun,
            long harga, String nomorPlat,
            int jumlahPintu, String bahanBakar) {

        super(merk, warna, tahun, harga, nomorPlat);

        this.jumlahPintu = jumlahPintu;
        this.bahanBakar = bahanBakar;
    }

    public void infoPintu() {
        System.out.println("Jumlah Pintu : " + jumlahPintu);
    }

    public String getBahanBakar() {
        return bahanBakar;
    }
}