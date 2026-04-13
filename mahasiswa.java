package com.mycompany.tugaspbo1;


public class mahasiswa {
 private String npm;
    private String nama;
    private double ipk;
    private int semester;

    // Constructor
    public mahasiswa(String npm, String nama, double ipk, int semester) {
        this.npm = npm;
        this.nama = nama;
        this.ipk = ipk;
        this.semester = semester;
    }

    // Method tampil data
    public void tampilData() {
        System.out.println("NPM      : " + npm);
        System.out.println("Nama     : " + nama);
        System.out.println("IPK      : " + ipk);
        System.out.println("Semester : " + semester);
    }
}