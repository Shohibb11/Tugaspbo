package com.mycompany.tugaspbo1.akademik;

public class matakuliah {
 private String kodeMk;
    private String namaMk;

    public matakuliah(String kodeMk, String namaMk) {
        this.kodeMk = kodeMk;
        this.namaMk = namaMk;
    }

    public void tampilMk() {
        System.out.println("Kode MK : " + kodeMk);
        System.out.println("Nama MK : " + namaMk);
    }
}
