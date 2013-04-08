#!/usr/local/bin/konoha

import("dscript.subproc");
import("JavaScript.Array");
import("JavaScript.String");
import("Type.File");
import("posix.path");
import("posix.process");

load("./decodeURI.k");

void main() {
	SubProc sp = new SubProc("/bin/mktemp");
	sp.setArgumentList(["-q", "/tmp/js.XXXXXX"]);
 	sp.fg();
 	String filename = sp.communicate("")[0].trim();
 	FILE tmp = new FILE(filename, "w");
	String input = decodeURI(System.getenv("QUERY_STRING").trim());
 	tmp.print(input);
 	tmp.flush();
 	tmp.close();
 	
	stdout.println("Content-Type: application/javascript; charset=utf-8\n");
 	
	sp = new SubProc("/usr/local/bin/konoha");
 	sp.setArgumentList(["-MFuelJS", "-ISyntax.GlobalVariable", "-IType.Float", "-IJavaScript.String", "-IJavaScript.Array", "-ISyntax.CStyleWhile", filename]);
	//stderr.println("start konoha -MFuelJS " + filename);
 	sp.fg();
 	stdout.println(sp.communicate("")[0]);
	//stderr.println("end konoha -MFuelJS " + filename);
}

main();
