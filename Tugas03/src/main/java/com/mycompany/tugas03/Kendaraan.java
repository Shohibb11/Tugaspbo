package com.mycompany.tugas03;

public class Kendaraan {

    String merk;
    String warna;
    int tahun;
    long harga;
    String nomorPlat;

    public Kendaraan() {
        System.out.println("Constructor tanpa parameter");
    }

    public Kendaraan(String merk, String warna) {
        this.merk = merk;
        this.warna = warna;
    }

    public Kendaraan(String merk, String warna, int tahun,
            long harga, String nomorPlat) {

        this.merk = merk;
        this.warna = warna;
        this.tahun = tahun;
        this.harga = harga;
        this.nomorPlat = nomorPlat;
    }

    public void tampilData() {
        System.out.println("Merk       : " + merk);
        System.out.println("Warna      : " + warna);
        System.out.println("Tahun      : " + tahun);
        System.out.println("Harga      : " + harga);
        System.out.println("Nomor Plat : " + nomorPlat);
    }

    public String getMerk() {
        return merk;
    }

    public void createData() {

        String sql = "INSERT INTO kendaraan VALUES('"
                + merk + "','"
                + warna + "',"
                + tahun + ","
                + harga + ",'"
                + nomorPlat + "')";

        System.out.println("SQL CREATE : " + sql);
    }

    public String readData() {
        return "SELECT * FROM kendaraan";
    }

    public void updateData() {

        String sql = "UPDATE kendaraan SET warna='"
                + warna
                + "' WHERE nomorPlat='"
                + nomorPlat + "'";

        System.out.println("SQL UPDATE : " + sql);
    }

    public void deleteData() {

        String sql = "DELETE FROM kendaraan WHERE nomorPlat='"
                + nomorPlat + "'";

        System.out.println("SQL DELETE : " + sql);
    }
}