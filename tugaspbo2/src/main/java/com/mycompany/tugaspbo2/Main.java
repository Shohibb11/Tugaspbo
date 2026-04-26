package com.mycompany.tugaspbo2;

public class Main {
    public static void main(String[] args) {

        // Object dari constructor default
        Mahasiswa mhs1 = new Mahasiswa();
        System.out.println("=== Mahasiswa 1 (Default) ===");
        mhs1.tampilData();
        System.out.println("Kategori: " + mhs1.getKategori());

        System.out.println();

        // Object dari constructor parameter
        Mahasiswa mhs2 = new Mahasiswa("2410020054", "Shahibul", 3.7);
        System.out.println("=== Mahasiswa 2 ===");
        mhs2.tampilData();
        System.out.println("Kategori: " + mhs2.getKategori());
    }
}