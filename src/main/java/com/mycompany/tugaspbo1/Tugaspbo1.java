package com.mycompany.tugaspbo1;
import com.mycompany.tugaspbo1.akademik.matakuliah;

public class Tugaspbo1 {

    public static void main(String[] args) {
        System.out.println("Program berhasil jalan!");
       
        mahasiswa mhs1 = new mahasiswa("2410020042", "Andhika", 3.80, 4);

        System.out.println("=== DATA MAHASISWA ===");
        mhs1.tampilData();

        System.out.println("\n=== DATA MATA KULIAH ===");

        
        matakuliah mk1 = new matakuliah("SI101", "Pemrograman Berbasis Objek");

        mk1.tampilMk();
        System.out.println();
    }
}
