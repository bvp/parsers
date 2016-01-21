/* Catalog parser for http://sokolov.ru */
package main

import ( // {{{
	"encoding/json"
	"errors"
	"log"
	"path/filepath"
	//	"sort"
	"sync"
	//	"regexp"
	"fmt"

	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	"io"
	"io/ioutil"
	//	"path/filepath"
	"net/http"
	"os"
	"regexp"
	"strconv"
	"strings"
	"time"
) // }}}

// Product structure
type Product struct { // {{{
	Sku            string `json:"sku"`
	Lang           string `json:"lang"`
	Category       string `json:"category"`
	Name           string `json:"name"`
	Metall         string `json:"metall"`
	MetallCategory string `json:"metallCat"`
	Who            string `json:"who"`
	Technology     string `json:"technology`
	Inserted       string `json:"inserted"`
	Pict           string `json:"pict"`
	Link           string `json:"link"`
	Desc           string `json:"desc"`
	//	Desc     map[string]string `json:"desc"`
	//	Desc []string `json:"desc"`
} // }}}

// Products array
type Products []Product

var ( // {{{
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	categories        = make(map[string]string)
	err, ferr         error
	counter           int
	failed            []string
	file              *os.File
	wg                sync.WaitGroup
	excludeList       = []string{
		"Коллекции",
		"Новинки",
	}
	metalSilverList = []string{
		"Золочёное",
		"Серебряные",
		"Серебро",
	}
	metalGoldList = []string{
		"Красное",
		"Белое",
		"Золотые",
	}
	metalChainList = []string{
		"Цепи",
	}
) // }}}

const ( // {{{
	baseURL    = "http://sokolov.ru"
	outputDir  = "exim/backup/images/"
	outputJSON = "sokolov.json"
) // }}}

func checkErr(msg string, err error) { // {{{
	if err != nil {
		fmt.Printf("%s - %s\n", msg, err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

/* func translit(s string) string { // {{{
	return unidecode.Unidecode(strings.Replace(s, " ", "_", -1))
} */ // }}}

func downloadFromURL(url string, outputDir string) error {
	defer wg.Done()
	fileName := getFilename(url)
	output, err := os.Create(outputDir + string(filepath.Separator) + fileName)
	if err != nil {
		log.Println("Ошибка при создании", outputDir+string(filepath.Separator)+fileName, "-", err)
		return err
	}
	defer output.Close()

	response, err := http.Get(url)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}
	defer response.Body.Close()

	n, err := io.Copy(output, response.Body)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}

	//	fmt.Println(n, "байтов загружено.")
	if n == 0 {
		return errors.New("Null size image")
	} else {
		return nil
	}
}

func contains(slice []string, item string) bool { // {{{
	set := make(map[string]struct{}, len(slice))
	for _, s := range slice {
		set[s] = struct{}{}
	}

	_, ok := set[item]
	return ok
} // }}}

func trimStr(str string) string { // {{{
	return strings.Replace(strings.Replace(strings.TrimSpace(str), "\t", "", -1), "\n", "", -1)
} // }}}

/* func removeDuplicates(elements []Product) []Product {
	// Use map to record duplicates as we find them.
	encountered := map[Product]bool{}
	result := []Product{}

	for v := range elements {
		if encountered[elements[v]] == true {
			// Do not add duplicate.
		} else {
			// Record this element as an encountered element.
			encountered[elements[v]] = true
			// Append to result slice.
			result = append(result, elements[v])
		}
	}
	// Return the new slice.
	return result
} */

func InSlice(arr []Product, val Product) bool {
	for _, v := range arr {
		if v.Sku == val.Sku {
			return true
		}
	}
	return false
}

func getPageDoc(url string) *goquery.Document { // {{{
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	checkErr("ERROR in getPageDoc::NewRequest", err)
	req.Header.Add("Accept-Encoding", "identity")
	req.Close = true

	response, err := client.Do(req)
	checkErr("ERROR in getPageDoc::Do", err)

	defer response.Body.Close()

	doc, err := goquery.NewDocumentFromResponse(response)
	checkErr("ERROR in getPageDoc::NewDocumentFromResponse", err)
	return doc
} // }}}

func getCategories() map[string]string { // {{{
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	getPageDoc(baseURL + "/jewelry-catalog/").Find("#catalogmenu > ul > li").Each(func(i int, s *goquery.Selection) {
		cat := s.Find("a").First().Text()
		if !contains(excludeList, cat) {
			s.Find("ul > li:nth-child(1) > ul > li").Each(func(j int, ss *goquery.Selection) {
				subCat := strings.TrimSpace(ss.Find("a").First().Text())
				subCatLink, _ := ss.Find("a").First().Attr("href")
				fmt.Printf("** %s - %s => %s\n", cat, subCat, baseURL+"/"+subCatLink)
				categories[cat+"///"+subCat] = baseURL + "/" + subCatLink
			})
		}
	})
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("#comp_2dce5194ad5727c611cb62e71261931f > div.text-center > ul > li").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	//	fmt.Printf("Найдено страниц для %s - %d\n", url, last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		// last := getPages(catLink)
		last := 5
		for i := 1; i <= last; i++ {
			currPage := fmt.Sprintf("%s%s%s", catLink, "?PAGEN_2=", strconv.Itoa(i))
			//			fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
			//		doc, _ = goquery.NewDocument(currPage)
			//			fmt.Printf("page - %d\n", i)
			getProducts(getPageDoc(currPage), cat)
		}
	}
	wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	//	ret, _ := doc.Html()
	//	fmt.Printf("getProducts doc - %s\n", ret)
	doc.Find("#comp_2dce5194ad5727c611cb62e71261931f > div.row > div").Each(func(i int, s *goquery.Selection) {
		link, _ := s.Find("a").First().Attr("href")
		// fmt.Printf("*** Получена ссылка для %s - %s\n", cat, strings.Replace(link, "	", "", -1))
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		wg.Add(1)
		go getProductInfo(baseURL+link, cat)
	})
} // }}}

func getProductInfo(url string, cat string) { // {{{
	defer wg.Done()
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp := getPageDoc(url)

	p := Product{}
	p.Lang = "ru"
	tmpSKU := docp.Find("#wrap > div:nth-child(2) > main > div.col-md-6.product-description > div.article.pull-left > span").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		return
	}
	p.Sku = tmpSKU
	p.Name = trimStr(docp.Find("#wrap > div:nth-child(2) > main > div.col-md-6.product-description > h1").Text())
	tmpPict, _ := docp.Find("div.product-images > img#current-image").First().Attr("src")
	fmt.Println("Finded pic -", tmpPict)
	p.Pict = outputDir + getFilename("http:"+tmpPict)
	wg.Add(1)
	go func() {
		time.Sleep(1 * time.Second)
		fderr := downloadFromURL("http:"+tmpPict, outputDir)
		checkErr("Download error in "+"http:"+tmpPict, fderr)
	}()
	p.Link = url
	p.Category = cat

	//	desc := make(map[string]string)
	docp.Find("#itemProperties > tbody > tr").Each(func(i int, sa *goquery.Selection) {
		title := trimStr(sa.Find("td:nth-child(1)").Text())
		value := trimStr(sa.Find("td:nth-child(2)").Text())
		switch title {
		case "Металл":
			p.Metall = value
			tmpMetal := strings.Split(p.Metall, " ")[0]
			if contains(metalGoldList, tmpMetal) {
				p.MetallCategory = "Золото"
			} else if contains(metalSilverList, tmpMetal) {
				p.MetallCategory = "Серебро"
			}
		case "Для кого":
			p.Who = value
		case "Технология":
			p.Technology = value
		case "Вставка":
			value = strings.Replace(value, " шт.", "", -1)
			r, _ := regexp.Compile("\\(\\d+\\)")
			p.Inserted = strings.TrimSpace(r.ReplaceAllString(value, ""))
		}
	})
	tmpDesc := trimStr(docp.Find("main > div.col-md-6.product-description > div.product-text").Text())
	if tmpDesc != "" {
		p.Desc = tmpDesc
	}

	//	fmt.Printf("%+v\n", p)

	if !InSlice(products, p) {
		_, wserr := io.WriteString(file, `"`+
			p.Sku+`";"`+ // Product code
			p.Lang+`";"`+ // Language
			p.MetallCategory+`///`+p.Category+`";"`+ // Category
			p.Name+`";"`+ // Product name
			p.Pict+`";"`+ // Detailed image
			strings.Split(p.Category, "///")[0]+`";"`+ // Short description
			p.Desc+`";"`+ // Description
			"Название: T["+p.Name+
			"]; Для кого: T["+p.Who+
			"]; Технология: T["+p.Technology+
			"]; Вставки: T["+p.Inserted+
			"]; Металл: T["+p.Metall+
			`]"`+"\n")
		checkErr("WriteString in getProductInfo", wserr)
		products = append(products, p)
		counter++
		fmt.Print(".")
	}
} // }}}

func main() { // {{{
	defer file.Close()
	ts := time.Now()
	fts := ts.Format("2006-01-02_15-04-05")
	file, ferr = os.Create("import_" + fts + ".csv")
	checkErr("Create file in main", ferr)
	_, wserr := io.WriteString(file, `"Product code";"Language";"Category";"Product name";"Detailed image";"Short description";"Description";"Features"`+"\n")
	checkErr("WriteString in main", wserr)

	if _, err := os.Stat(outputDir); os.IsNotExist(err) {
		os.MkdirAll(outputDir, 0777)
	}

	fmt.Println("* Начинаем сбор страниц")
	categories = getCategories()
	//	categories["Браслеты из серебра"] = "https://sokolov.ru/jewelry-catalog/bracelets/silver/"

	walkinPages()
	//	doc, _ := goquery.NewDocument(baseURL + "?page=all")
	//	getProducts(doc)

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	if len(failed) > 0 {
		fmt.Printf("Не обработано - %d\n", len(failed))
		for _, fail := range failed {
			fmt.Printf("FAIL => %s\n", fail)
		}
	}
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
} // }}}
