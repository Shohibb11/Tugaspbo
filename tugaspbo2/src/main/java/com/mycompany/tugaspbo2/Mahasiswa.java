package com.mycompany.tugaspbo2;

public class Mahasiswa {
    String nim;
    String nama;
    double ipk;

    // Constructor default
    public Mahasiswa() {
        nim = "0000";
        nama = "Default";
        ipk = 0.0;
    }

    // Constructor dengan parameter
    public Mahasiswa(String nim, String nama, double ipk) {
        this.nim = nim;
        this.nama = nama;
        this.ipk = ipk;
    }

    // Prosedur (tanpa nilai balik)
    public void tampilData() {
        System.out.println("NIM  : " + nim);
        System.out.println("Nama : " + nama);
        System.out.println("IPK  : " + ipk);
    }

    // Fungsi (dengan nilai balik)
    public String getKategori() {
        if (ipk >= 3.5) {
            return "Cumlaude";
        } else if (ipk >= 3.0) {
            return "Baik";
        } else {
            return "Cukup";
        }
    }
}