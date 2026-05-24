package com.mycompany.tugas03;

public class Tugas03 {

    public static void main(String[] args) {

        Kendaraan k1 = new Kendaraan();

        System.out.println("====================");

        Kendaraan k2 = new Kendaraan("Toyota", "Hitam");

        k2.tahun = 2022;
        k2.harga = 300000000L;
        k2.nomorPlat = "KH 1234 AB";

        k2.tampilData();

        System.out.println("Merk Kendaraan : " + k2.getMerk());

        k2.createData();

        System.out.println("SQL READ : " + k2.readData());

        k2.updateData();

        k2.deleteData();

        System.out.println("====================");

        Kendaraan k3 = new Kendaraan(
                "Honda",
                "Merah",
                2021,
                250000000L,
                "KH 5678 CD"
        );

        k3.tampilData();

        System.out.println("====================");

        Mobil m1 = new Mobil(
                "Mitsubishi",
                "Putih",
                2023,
                450000000L,
                "KH 9999 EF",
                4,
                "Pertalite"
        );

        m1.tampilData();

        m1.infoPintu();

        System.out.println("Bahan Bakar : " + m1.getBahanBakar());

        m1.createData();

        System.out.println("SQL READ : " + m1.readData());

        m1.updateData();

        m1.deleteData();
    }
}