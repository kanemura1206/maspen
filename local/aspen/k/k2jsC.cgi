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
 	sp.bg();
 	String filename = sp.communicate("")[0].trim();
 	FILE tmp = new FILE(filename, "w");
	String input = decodeURI(System.getenv("QUERY_STRING").trim());
 	tmp.print(input);
 	tmp.flush();
 	tmp.close();
 	sp = new SubProc("/usr/local/bin/konoha");
 	sp.setArgumentList(["-ISyntax.GlobalVariable", "-IType.Float", "-IJavaScript.String", "-IJavaScript.Array", "-ISyntax.CStyleWhile", "-c", filename]);
	//sp.setArgumentList(["-c", filename]);
 	//sp.setArgumentList(["-MFuelJS", "-Icstyle", "-IJavaStyle.Object", "-ISyntax.JavaStyleClass", "-IType.StaticVar", "-ISyntax.GlobalVariable", "-IMiniKonoha.NameSpace", "-IJavaScript.String", "-IJavaScript.Regexp", "-IJavaScript.Array", "-IMiniKonoha.Map", "-Ikonoha.iterator", filename]);
	stderr.println("compile start konoha -MFuelJS " + filename);
 	sp.bg();
 	stdout.println("Content-Type: application/javascript; charset=utf-8\n");
 	stdout.println(sp.communicate("")[0]);
	stderr.println("compile end konoha -MFuelJS " + filename);
}

main();
