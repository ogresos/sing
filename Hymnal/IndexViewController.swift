//
//  IndexViewController.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/14/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit

import UIKit
import CoreData

class IndexViewController: UICollectionViewController, UINavigationControllerDelegate, NSFetchedResultsControllerDelegate, XMLParserDelegate, UISearchResultsUpdating {




    
    @IBOutlet weak var indexCollectionView: UICollectionView!
    @IBOutlet var searchBarContainer: UIView?
    let searchController = UISearchController(searchResultsController: nil)
    

    let appDelegate = UIApplication.shared.delegate as! AppDelegate
    var managedObjectContext: NSManagedObjectContext? = nil
    
    
    // Model vars
    var hymns = [NSManagedObject]()
    var filteredHymns = [NSManagedObject]()
    var theHymn: NSManagedObject!
    var selectedIndexPath: IndexPath = []
    
//    var filteredData = [NSManagedObject]()
    var isDataFiltered: Bool = false
    
    // Parser vars
    // TODO: Parser stuff should be moved to it's own class
    var parser: XMLParser!
    var elements = NSMutableDictionary()
    var element = String()
    var hymn: NSManagedObject!
    var hymnTitle = NSMutableString()
    var hymnNumber = String()
    var stanzaText = NSMutableString()
    var stanzaNumber = String()
    var stanzaType = String()
    var stanzaOrder = 0
    
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Search setup
        searchController.searchResultsUpdater = self
        searchController.dimsBackgroundDuringPresentation = false
        searchController.hidesNavigationBarDuringPresentation = false
        definesPresentationContext = true
        
        searchController.searchBar.autoresizingMask = [UIViewAutoresizing.flexibleWidth, UIViewAutoresizing.flexibleHeight]
        self.searchBarContainer?.addSubview(searchController.searchBar)
        searchController.searchBar.sizeToFit()
        searchController.searchBar.backgroundImage = UIImage(named:"NavigationBar")
        searchController.searchBar.tintColor = UIColor(red: 126.0/255.0, green: 211.0/255.0, blue: 33.0/255.0, alpha: 1.0)
        
        self.managedObjectContext = appDelegate.managedObjectContext
        let managedContext = self.fetchedResultsController.managedObjectContext
        let fetchRequest = NSFetchRequest<NSFetchRequestResult>(entityName: "Hymn")
        let sortDescriptors = [NSSortDescriptor(key: "number", ascending:true, selector: #selector(NSString.localizedStandardCompare))]
        fetchRequest.sortDescriptors = sortDescriptors
        
        do {
            let results =
                try managedContext.fetch(fetchRequest)
            hymns = results as! [NSManagedObject]
            
        } catch let error as NSError {
            print("Could not fetch \(error), \(error.userInfo)")
        }
        
        if (hymns.count == 0) {
            //resetToDefaultData()
            createHymnsFromXML()
        }
        
    }
    
    override func viewWillAppear(_ animated: Bool) {
        self.navigationController?.delegate = self;
    }
    

    
    //MARK:UISearchResultsUpdating
    
    public func updateSearchResults(for searchController: UISearchController) {
        filterData()
        indexCollectionView?.reloadData()
    }
    
    func filterData() {

        if (searchController.searchBar.text != "") {
            let searchPredicate = NSPredicate(format: "SELF.title CONTAINS[c] %@ || SELF.number CONTAINS[c] %@", searchController.searchBar.text!, searchController.searchBar.text!)
            let array = (hymns as NSArray).filtered(using: searchPredicate)
            
            filteredHymns = array as! [NSManagedObject]
            isDataFiltered = true
        }
        else {
            isDataFiltered = false
        }
    }
    
    

    
    
    
    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
    
    override func prepare(for segue: UIStoryboardSegue, sender: Any?) {
        let indexPath = indexCollectionView.indexPath(for: sender as! UICollectionViewCell)
        
        if((indexPath) != nil) {
            let hvc: HymnViewController = (segue.destination as? HymnViewController)!
            selectedIndexPath = indexPath!
            
            let hymn = isDataFiltered ? filteredHymns[(indexPath?.row)!] : hymns[(indexPath?.row)!]
            self.theHymn = hymn
            
            hvc.theHymn = self.theHymn
            //hvc.useLayoutToLayoutNavigationTransitions = true
        }
    }
    
    
//    func indexPathfor(hymn:Hymn) {
//        // Need to find the indexPath even when search
//        let number:Int? = hymn.number.toInt()
//        return (number - 1)
//    }

    
    @IBAction func unwindToIndex(segue:UIStoryboardSegue) {
        print("Attempting to unwind")
    }
    
    
    // UICollectionView methods
    
    // MARK: UICollectionViewDataSource
    
    override func numberOfSections(in collectionView: UICollectionView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }
    
    
    override func collectionView(_ collectionView: UICollectionView, numberOfItemsInSection section: Int) -> Int {
        return isDataFiltered ? filteredHymns.count : hymns.count
    }
    
    //    interspacing
    func collectionView(_ collectionView: UICollectionView,
                        layout collectionViewLayout: UICollectionViewLayout,
                        minimumInteritemSpacingForSectionAt section: Int) -> CGFloat {
        return 1.0
    }
    
    func collectionView(_ collectionView: UICollectionView, layout
        collectionViewLayout: UICollectionViewLayout,
                        minimumLineSpacingForSectionAt section: Int) -> CGFloat {
        return 15.0
    }
    
    override func collectionView(_ collectionView: UICollectionView, cellForItemAt indexPath: IndexPath) -> UICollectionViewCell {
        let cell = indexCollectionView.dequeueReusableCell(withReuseIdentifier: "IndexCell", for: indexPath) as! IndexCollectionViewCell
        let hymn = isDataFiltered ? filteredHymns[indexPath.row] : hymns[indexPath.row]
        cell.initWith(theHymn: hymn)
        
        return cell
    }
    
    
    func navigationController(_ navigationController: UINavigationController, didShow viewController: UIViewController, animated: Bool) {
        
        if (viewController.isKind(of: HymnViewController.self)) {
            let hvc: HymnViewController = (viewController as? HymnViewController)!
            hvc.collectionView?.dataSource = hvc
            hvc.collectionView?.delegate = hvc
            //print("scroll to indexPath", selectedIndexPath)
            //hvc.collectionView?.scrollToItem(at: selectedIndexPath, at: UICollectionViewScrollPosition.centeredVertically, animated: true)
            
        }
        else {
            self.collectionView?.dataSource = self
            self.collectionView?.delegate = self
        }
    }
    
    // MARK: - Fetched results controller
    
    var fetchedResultsController: NSFetchedResultsController<Hymn> {
        if _fetchedResultsController != nil {
            return _fetchedResultsController!
        }
        
        let fetchRequest: NSFetchRequest<Hymn> = Hymn.fetchRequest()
        
        // Set the batch size to a suitable number.
        fetchRequest.fetchBatchSize = 100
        
        // Edit the sort key as appropriate.
        let sortDescriptor = NSSortDescriptor(key: "number", ascending: true)
        
        fetchRequest.sortDescriptors = [sortDescriptor]
        
        // Edit the section name key path and cache name if appropriate.
        // nil for section name key path means "no sections".
        let aFetchedResultsController = NSFetchedResultsController(fetchRequest: fetchRequest, managedObjectContext: self.managedObjectContext!, sectionNameKeyPath: nil, cacheName: nil)
        aFetchedResultsController.delegate = self
        _fetchedResultsController = aFetchedResultsController
        
        do {
            try _fetchedResultsController!.performFetch()
        } catch {
            // Replace this implementation with code to handle the error appropriately.
            // fatalError() causes the application to generate a crash log and terminate. You should not use this function in a shipping application, although it may be useful during development.
            let nserror = error as NSError
            fatalError("Unresolved error \(nserror), \(nserror.userInfo)")
        }
        
        return _fetchedResultsController!
    }
    var _fetchedResultsController: NSFetchedResultsController<Hymn>? = nil
    
    
    
    
    
    func createHymnsFromXML() {
        //        for index in 0...
        
        let fileManager = FileManager.default
        //let documentsPath = NSSearchPathForDirectoriesInDomains(.documentDirectory, .userDomainMask, true)[0]
        let bundlePath = Bundle.main.bundlePath
        let enumerator:FileManager.DirectoryEnumerator = fileManager.enumerator(atPath: bundlePath)!
        
        while let element = enumerator.nextObject() as? String {
            
            if element.hasSuffix("xml") { // checks the extension
                
                let path = Bundle.main.path(forResource:element, ofType: nil)
                if(path != nil) {
                    print(element)
                    let url = NSURL(fileURLWithPath: path!) as URL
                    self.parser = XMLParser(contentsOf:url)
                    self.parser.delegate = self
                    self.parser.parse()
                }
                
            }
        }
        //let documentsPath = NSSearchPathForDirectoriesInDomains(.documentDirectory, .userDomainMask, true)[0]
        print("documents", bundlePath)
        //let path = documentsPath.stringByApp
        //var files = extractAllFiles(atPath: documentsPath, withExtension: "xml")
        
        //print("The files", files)
        
    }
    
    func extractAllFiles(atPath path: String, withExtension fileExtension:String) -> [String] {
        let pathURL = NSURL(fileURLWithPath: path, isDirectory: true)
        var allFiles: [String] = []
        let fileManager = FileManager.default
        //let enumerator:NSDirectoryEnumerator = manager.enumeratorAtURL(url, includingPropertiesForKeys: keys, options: NSDirectoryEnumerationOptions(), errorHandler: nil)
        if let enumerator = fileManager.enumerator(atPath: path) {
            print("file!")
            for file in enumerator {
                if let path = NSURL(fileURLWithPath: file as! String, relativeTo: pathURL as URL).path
                    , path.hasSuffix(".\(fileExtension)"){
                    allFiles.append(path)
                }
            }
        }
        return allFiles
    }
    
    func parser(_ parser: XMLParser, didStartElement elementName: String, namespaceURI: String?, qualifiedName qName: String?, attributes attributeDict: [String : String])
    {
        element = elementName
        if ((elementName as NSString).isEqual(to: "english")) {
            hymnNumber = attributeDict["num"]!
            stanzaOrder = 0 // Reset for each hymn
            
            // Now we have enough info to create the hymn object
            hymn = createHymn(title: hymnTitle as String, number: hymnNumber)
            print("Created hymn:", hymn)
        }
        if ((elementName as NSString).isEqual(to: "stanza")) {
            if (attributeDict["number"] != nil) {
                stanzaNumber = attributeDict["number"]!
            }
            
            stanzaType = attributeDict["type"]!
            stanzaOrder += 1
        }
        
    }
    
    func parser(_ parser: XMLParser, foundCharacters string: String)
    {
        if element.isEqual("title") {
            hymnTitle.append(string)
        } else if element.isEqual("stanza") {
            stanzaText.append(string)
        }
    }
    
    func parser(_ parser: XMLParser, didEndElement elementName: String, namespaceURI: String?, qualifiedName qName: String?)
    {
        if (elementName as NSString).isEqual(to: "stanza") {
            createStanza(order: stanzaOrder, number: stanzaNumber, type: stanzaType, text: stanzaText as String, hymn: hymn)
            stanzaText = ""
        }
        if (elementName as NSString).isEqual(to: "hymn") {
            hymnTitle = ""
        }
    }
    
    
    
    func createHymn(title: String, number: String) -> NSManagedObject {
        let managedContext = self.fetchedResultsController.managedObjectContext
        let entity =  NSEntityDescription.entity(forEntityName: "Hymn",
                                                 in:managedContext)
        
        let hymn = NSManagedObject(entity: entity!,
                                   insertInto: managedContext)
        hymn.setValue(title, forKey: "title")
        hymn.setValue(number, forKey: "number")
        
        do {
            try managedContext.save()
            hymns.append(hymn)
            //self.stanzasTableView.reloadData()
        } catch let error as NSError  {
            print("Could not save \(error), \(error.userInfo)")
        }
        return hymn
    }
    
    func createStanza(order: Int, number: String, type: String, text: String, hymn: NSManagedObject) {
        
        let managedContext = self.fetchedResultsController.managedObjectContext
        let entity =  NSEntityDescription.entity(forEntityName: "Stanza",
                                                 in:managedContext)
        
        let stanza = NSManagedObject(entity: entity!,
                                     insertInto: managedContext)
        stanza.setValue(order, forKey: "order")
        stanza.setValue(number, forKey: "number")
        stanza.setValue(type, forKey: "type")
        stanza.setValue(text, forKey: "text")
        //print(hymn)
        stanza.setValue(hymn, forKey: "hymn")
        
        do {
            try managedContext.save()
            //self.stanzasTableView.reloadData()
        } catch let error as NSError  {
            print("Could not save \(error), \(error.userInfo)")
        }
        
    }
    
}

