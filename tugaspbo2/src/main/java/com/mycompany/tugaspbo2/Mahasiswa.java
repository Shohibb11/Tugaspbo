package com.mycompany.tugaspbo2;

public class Mahasiswa {
    String nim;
    String nama;
    double ipk;

    public Mahasiswa() {
        nim = "0000";
        nama = "Default";
        ipk = 0.0;
    }

    public Mahasiswa(String nim, String nama, double ipk) {
        this.nim = nim;
        this.nama = nama;
        this.ipk = ipk;
    }

    public void tampilData() {
        System.out.println("NIM  : " + nim);
        System.out.println("Nama : " + nama);
        System.out.println("IPK  : " + ipk);
    }

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
