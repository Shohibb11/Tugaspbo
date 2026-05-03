package com.mycompany.uts;

public class Penerbit {
    int id;
    String namaPenerbit;
    String alamatPenerbit;

    public Penerbit(int id, String namaPenerbit, String alamatPenerbit) {
        this.id = id;
        this.namaPenerbit = namaPenerbit;
        this.alamatPenerbit = alamatPenerbit;
    }

    public void index() {
        System.out.println("SELECT * FROM penerbit;");
    }

    public void create() {
        System.out.println("FORM TAMBAH PENERBIT");
    }

    public void store() {
        System.out.println("INSERT INTO penerbit (id, namaPenerbit, alamatPenerbit) VALUES (" 
                + id + ", '" + namaPenerbit + "', '" + alamatPenerbit + "');");
    }

    public void edit() {
        System.out.println("FORM EDIT PENERBIT");
    }

    public void update() {
        System.out.println("UPDATE penerbit SET namaPenerbit='" + namaPenerbit 
                + "', alamatPenerbit='" + alamatPenerbit + "' WHERE id=" + id + ";");
    }

    public void destroy() {
        System.out.println("DELETE FROM penerbit WHERE id=" + id + ";");
    }
}